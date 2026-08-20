<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\DueCollect;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\Request;

class ZSystDueController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $data = DueCollect::with('user:id,name', 'party:id,name,email,phone,type')
            ->when($search, function ($query) use ($search) {
                $query->where('invoiceNumber', 'like', '%'.$search.'%')
                    ->orWhere('totalDue', 'like', '%'.$search.'%')
                    ->orWhere('paymentType', 'like', '%'.$search.'%');
            })
            ->where('business_id', auth()->user()->business_id)
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function duesList(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $query = Party::select('id', 'name', 'type', 'due', 'phone')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('type', 'like', '%'.$search.'%')
                    ->orWhere('address', 'like', '%'.$search.'%');
            })
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->where('due', '>', 0)
            ->where('business_id', auth()->user()->business_id);

        $data = (clone $query)->latest()->paginate($request->input('per_page', 10));
        $total_payable = (clone $query)->where('type', 'Supplier')->sum('due');
        $total_receivable = (clone $query)->whereIn('type', ['Retailer', 'Wholesaler'])->sum('due');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_receivable' => $total_receivable,
            'total_payable' => $total_payable,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $party = Party::find($request->party_id);

        $request->validate([
            'paymentType' => 'required|string',
            'paymentDate' => 'required|string',
            'payDueAmount' => 'required|numeric',
            'party_id' => 'required|exists:parties,id',
            'invoiceNumber' => 'nullable|exists:'.($party->type == 'Supplier' ? 'purchases' : 'sales').',invoiceNumber',
        ]);

        // Find invoice if invoiceNumber provided
        $invoice = null;
        if ($request->invoiceNumber) {
            if ($party->type == 'Supplier') {
                $invoice = Purchase::where('invoiceNumber', $request->invoiceNumber)
                    ->where('party_id', $request->party_id)
                    ->first();
            } else {
                $invoice = Sale::where('invoiceNumber', $request->invoiceNumber)
                    ->where('party_id', $request->party_id)
                    ->first();
            }

            if (! isset($invoice)) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_INVOICE_NOT_FOUND,
                    __('errors.invoice_not_found'),
                    ['invoiceNumber' => $request->invoiceNumber, 'party_id' => $request->party_id]
                );
            }

            if ($invoice->dueAmount < $request->payDueAmount) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_INVOICE_DUE_EXCEEDED,
                    __('errors.invoice_due_exceeded', ['due' => $invoice->dueAmount]),
                    [
                        'invoice_due' => $invoice->dueAmount,
                        'payment_amount' => $request->payDueAmount,
                    ]
                );
            }
        }

        if (! $request->invoiceNumber) {
            if ($request->payDueAmount > $party->opening_balance) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_OPENING_BALANCE_EXCEEDED,
                    __('errors.opening_balance_exceeded', ['balance' => $party->opening_balance]),
                    [
                        'opening_balance' => $party->opening_balance,
                        'payment_amount' => $request->payDueAmount,
                    ]
                );
            }
        }

        $data = DueCollect::create($request->all() + [
            'user_id' => auth()->id(),
            'business_id' => auth()->user()->business_id,
            'sale_id' => $party->type != 'Supplier' && isset($invoice) ? $invoice->id : null,
            'purchase_id' => $party->type == 'Supplier' && isset($invoice) ? $invoice->id : null,
            'totalDue' => isset($invoice) ? $invoice->dueAmount : $party->due,
            'dueAmountAfterPay' => isset($invoice) ? ($invoice->dueAmount - $request->payDueAmount) : ($party->due - $request->payDueAmount),
        ]);

        if (isset($invoice)) {
            $invoice->update([
                'dueAmount' => $invoice->dueAmount - $request->payDueAmount,
            ]);
        }

        $business = Business::findOrFail(auth()->user()->business_id);
        $business_name = $business->companyName;
        $business->update([
            'remainingShopBalance' => $party->type == 'Supplier' ? ($business->remainingShopBalance - $request->payDueAmount) : ($business->remainingShopBalance + $request->payDueAmount),
        ]);

        $party->update([
            'due' => $party->due - $request->payDueAmount,
            'opening_balance' => $request->invoiceNumber ? $party->opening_balance : $party->opening_balance - $request->payDueAmount,
        ]);

        if (config('zsyst.message_enabled')) {
            sendMessage($party->phone, dueCollectMessage($data, $party, $business_name, $request->invoiceNumber));
        }

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data->load('user:id,name', 'party:id,name,email,phone,type'),
        ]);
    }
}
