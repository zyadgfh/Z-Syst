<?php

namespace App\Http\Controllers\Api;

use App\Events\DuePaymentReceived;
use App\Events\MultiPaymentProcessed;
use App\Models\Sale;
use App\Models\Party;
use App\Models\Business;
use App\Models\Purchase;
use App\Models\DueCollect;
use Illuminate\Http\Request;
use App\Traits\DateFilterTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AcnooDueController extends Controller
{
    Use DateFilterTrait;

    public function index()
    {
        $query = DueCollect::with('user:id,name,role', 'party:id,name,email,phone,type,address', 'branch:id,name,phone,address','transactions:id,platform,transaction_type,amount,date,invoice_no,reference_id,payment_type_id,meta', 'transactions.paymentType:id,name')
                ->where('business_id', auth()->user()->business_id);

        // Apply date filter
        if(request('duration')){
            $this->applyDateFilter($query, request('duration'), 'paymentDate', request('from_date'), request('to_date'));
        }

        $data = $query->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $party = Party::find($request->party_id);

        $request->validate([
            'paymentDate' => 'required|string',
            'payDueAmount' => 'nullable|numeric',
            'party_id' => 'required|exists:parties,id',
            'invoiceNumber' => 'nullable|exists:' . ($party->type == 'Supplier' ? 'purchases' : 'sales') . ',invoiceNumber',
        ]);

        $user = auth()->user();
        $action_branch_id = $user->branch_id ?? $user->active_branch_id;
        $payments = $request->payments ?? [];

        $payDueAmount = collect($payments)
            ->reject(fn($p) => strtolower($p['type'] ?? '') === 'cheque')
            ->sum(fn($p) => $p['amount'] ?? 0);

        if ($action_branch_id != $party->branch_id && !$request->invoiceNumber) {
            return response()->json([
                'message' => __('You must select an invoice when login any branch.')
            ], 400);
        }

        $branch_id = null;
        if ($request->invoiceNumber) {
            if ($party->type == 'Supplier') {
                $invoice = Purchase::where('invoiceNumber', $request->invoiceNumber)->where('party_id', $request->party_id)->first();
            } else {
                $invoice = Sale::where('invoiceNumber', $request->invoiceNumber)->where('party_id', $request->party_id)->first();
            }

            if (!isset($invoice)) {
                return response()->json([
                    'message' => 'Invoice Not Found.'
                ], 404);
            }

            if (!auth()->user()->active_branch) {
                if (isset($invoice) && isset($invoice->branch_id)) {
                    $branch_id = $invoice->branch_id;
                }
            }

            if ($invoice->dueAmount < $request->payDueAmount) {
                return response()->json([
                    'message' => 'Invoice due is ' . $invoice->dueAmount . '. You can not pay more then the invoice due amount.'
                ], 400);
            }
        }

        if (!$request->invoiceNumber) {
            if ($party->type == 'Supplier') {
                $all_invoice_due = Purchase::where('party_id', $request->party_id)->sum('dueAmount');
            } else {
                $all_invoice_due = Sale::where('party_id', $request->party_id)->sum('dueAmount');
            }

            if (($all_invoice_due + $request->payDueAmount) > $party->due) {
                return response()->json([
                    'message' => __('You can pay only '. $party->due - $all_invoice_due .', without selecting an invoice.')
                ], 400);
            }

            if ($party->opening_balance_type == 'due') {
                $party->update([
                    'opening_balance' => max(0, $party->opening_balance - $payDueAmount),
                ]);
            }

        }

        $data = DueCollect::create($request->all() + [
                    'user_id' => auth()->id(),
                    'business_id' => auth()->user()->business_id,
                    'sale_id' => $party->type != 'Supplier' && isset($invoice) ? $invoice->id : NULL,
                    'purchase_id' => $party->type == 'Supplier' && isset($invoice) ? $invoice->id : NULL,
                    'totalDue' => isset($invoice) ? $invoice->dueAmount : $party->due,
                    'dueAmountAfterPay' => isset($invoice) ? ($invoice->dueAmount - $payDueAmount) : ($party->due - $payDueAmount),
                ]);

        if (isset($invoice)) {
            $invoice->update([
                'dueAmount' => $invoice->dueAmount - $payDueAmount
            ]);
        }

        $party->type == 'Supplier' ? updateBalance($payDueAmount, 'decrement', $branch_id) : updateBalance($payDueAmount, 'increment', $branch_id);

        $party->update([
            'due' => $party->due - $payDueAmount
        ]);

        // MultiPaymentProcessed Event
        event(new MultiPaymentProcessed(
            $payments,
            $data->id,
            'due_collect',
            $payDueAmount,
            $party->id
        ));

        // Send SMS
        event(new DuePaymentReceived($data));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data->load('user:id,name,role', 'party:id,name,email,phone,type,address', 'branch:id,name,phone,address','transactions:id,platform,transaction_type,amount,date,invoice_no,reference_id,payment_type_id,meta', 'transactions.paymentType:id,name'),
        ]);
    }

    public function invoiceWiseDue()
    {
        $data = Sale::select('id','dueAmount', 'paidAmount', 'totalAmount', 'invoiceNumber', 'saleDate', 'meta')
            ->where('business_id', auth()->user()->business_id)
            ->whereNull('party_id')
            ->where('dueAmount', '>', 0)
            ->latest()->paginate(10);

        // Sum only for paginate  data
        $total_receivable = $data->getCollection()->sum('dueAmount');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_receivable' => (float) $total_receivable,
            'data' => $data,
        ]);
    }

    public function collectInvoiceDue(Request $request)
    {
        $business_id = auth()->user()->business_id;

        $request->validate([
            'paymentDate' => 'required|string',
            'payDueAmount' => 'nullable|numeric',
            'invoiceNumber' => 'required|string|exists:sales,invoiceNumber',
        ]);

        DB::beginTransaction();
        try {
            $invoice = Sale::where('business_id', $business_id)->where('invoiceNumber', $request->invoiceNumber)->whereNull('party_id')->first();

            if (!$invoice) {
                return response()->json([
                    'message' => 'Invoice Not Found.'
                ], 404);
            }
            $payments = $request->payments ?? [];

            $payDueAmount = collect($payments)
                ->reject(fn($p) => strtolower($p['type'] ?? '') === 'cheque')
                ->sum(fn($p) => $p['amount'] ?? 0);


            if ($invoice->dueAmount < $payDueAmount) {
                return response()->json([
                    'message' => 'Invoice due is ' . $invoice->dueAmount . '. You cannot pay more than the invoice due amount.'
                ], 400);
            }

            $data = DueCollect::create([
                'user_id' => auth()->id(),
                'business_id' => $business_id,
                'sale_id' => $invoice->id,
                'invoiceNumber' => $request->invoiceNumber,
                'totalDue' => $invoice->dueAmount,
                'dueAmountAfterPay' => $invoice->dueAmount - $payDueAmount,
                'payDueAmount' => $payDueAmount,
                'payment_type_id' => $request->payment_type_id,
                'paymentDate' => $request->paymentDate,
            ]);

            $invoice->update([
                'dueAmount' => $invoice->dueAmount - $payDueAmount
            ]);

            $business = Business::findOrFail($business_id);
            $business->update([
                'remainingShopBalance' => $business->remainingShopBalance + $payDueAmount
            ]);

            sendNotifyToUser($data->id, route('business.dues.index', ['id' => $data->id]), __('Due Collection has been created.'), $business_id);

            // MultiPaymentProcessed Event
            event(new MultiPaymentProcessed(
                $payments,
                $data->id,
                'due_collect',
                $payDueAmount,
            ));

            DB::commit();

            return response()->json([
                'message' => __('Data fetched successfully.'),
                'data' => $data->load('user:id,name', 'party:id,name,email,phone,type,address','transactions:id,platform,transaction_type,amount,date,invoice_no,reference_id,payment_type_id,meta', 'transactions.paymentType:id,name'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }

    }
}

