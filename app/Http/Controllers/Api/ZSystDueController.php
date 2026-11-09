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
                $query->where('invoice_number', 'like', '%'.$search.'%')
                    ->orWhere('totalDue', 'like', '%'.$search.'%')
                    ->orWhere('payment_type', 'like', '%'.$search.'%');
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
            'payment_type' => 'required|string',
            'paymentDate' => 'required|string',
            'payDueAmount' => 'required|numeric',
            'party_id' => 'required|exists:parties,id',
            'invoice_number' => 'nullable|exists:'.($party->type == 'Supplier' ? 'purchases' : 'sales').',invoice_number',
        ]);

        // Find invoice if invoice_number provided
        $invoice = null;
        if ($request->invoice_number) {
            if ($party->type == 'Supplier') {
                $invoice = Purchase::where('invoice_number', $request->invoice_number)
                    ->where('party_id', $request->party_id)
                    ->first();
            } else {
                $invoice = Sale::where('invoice_number', $request->invoice_number)
                    ->where('party_id', $request->party_id)
                    ->first();
            }

            if (! isset($invoice)) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_INVOICE_NOT_FOUND,
                    __('errors.invoice_not_found'),
                    ['invoice_number' => $request->invoice_number, 'party_id' => $request->party_id]
                );
            }

            if ($invoice->due_amount < $request->payDueAmount) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_INVOICE_DUE_EXCEEDED,
                    __('errors.invoice_due_exceeded', ['due' => $invoice->due_amount]),
                    [
                        'invoice_due' => $invoice->due_amount,
                        'payment_amount' => $request->payDueAmount,
                    ]
                );
            }
        }

        if (! $request->invoice_number) {
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

        $data = DueCollect::create($request->only([
        'business_id',
        'party_id',
        'user_id',
        'sale_id',
        'purchase_id',
        'invoice_number',
        'totalDue',
        'dueAmountAfterPay',
        'payDueAmount',
        'payment_type',
        'paymentDate'
    ]) + [
            'user_id' => auth()->id(),
            'business_id' => auth()->user()->business_id,
            'sale_id' => $party->type != 'Supplier' && isset($invoice) ? $invoice->id : null,
            'purchase_id' => $party->type == 'Supplier' && isset($invoice) ? $invoice->id : null,
            'totalDue' => isset($invoice) ? $invoice->due_amount : $party->due,
            'dueAmountAfterPay' => isset($invoice) ? ($invoice->due_amount - $request->payDueAmount) : ($party->due - $request->payDueAmount),
        ]);

        if (isset($invoice)) {
            $invoice->update([
                'due_amount' => $invoice->due_amount - $request->payDueAmount,
            ]);
        }

        $business = Business::findOrFail(auth()->user()->business_id);
        $business_name = $business->companyName;
        $business->update([
            'remaining_shop_balance' => $party->type == 'Supplier' ? ($business->remainingShopBalance - $request->payDueAmount) : ($business->remainingShopBalance + $request->payDueAmount),
        ]);

        $party->update([
            'due' => $party->due - $request->payDueAmount,
            'opening_balance' => $request->invoice_number ? $party->opening_balance : $party->opening_balance - $request->payDueAmount,
        ]);

        if (config('services.message.enabled')) {
            sendMessage($party->phone, dueCollectMessage($data, $party, $business_name, $request->invoice_number));
        }

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data->load('user:id,name', 'party:id,name,email,phone,type'),
        ]);
    }
}
