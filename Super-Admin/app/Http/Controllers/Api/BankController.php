<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function index()
    {
        $data = PaymentType::with('branch:id,name')->where('business_id', auth()->user()->business_id)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'opening_balance' => 'nullable|numeric|min:0',
            'opening_date' => 'nullable|date',
            'show_in_invoice' => 'boolean',
            'meta' => 'nullable|array',
            'meta.account_number' => 'nullable|string|max:255',
            'meta.routing_number' => 'nullable|string|max:255',
            'meta.upi_id' => 'nullable|string|max:255',
            'meta.bank_name' => 'nullable|string|max:255',
            'meta.account_holder' => 'nullable|string|max:255',
            'meta.branch' => 'nullable|string|max:255',
        ]);

        $forbidden = ['cash', 'cheque'];
        if (in_array(strtolower($request->name), $forbidden)) {
            return response()->json([
                'message' => __('You cannot create a bank account with this name.'),
            ], 422);
        }

        $data = PaymentType::create($request->except('business_id', 'show_in_invoice', 'balance', 'opening_balance', 'meta') + [
            'business_id' => auth()->user()->business_id,
            'opening_balance' => $request->opening_balance ?? 0,
            'balance' => $request->opening_balance ?? 0,
            'show_in_invoice' => $request->show_in_invoice,
            'meta' => [
                'account_number' => $request->account_number,
                'routing_number' => $request->routing_number,
                'upi_id' => $request->upi_id,
                'bank_name' => $request->bank_name,
                'account_holder' => $request->account_holder,
                'branch' => $request->branch,
            ]
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $data = PaymentType::with('branch:id,name')->where('business_id', auth()->user()->business_id)->find($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'opening_balance' => 'nullable|numeric|min:0',
            'opening_date' => 'nullable|date',
            'show_in_invoice' => 'boolean',
            'meta' => 'nullable|array',
            'meta.account_number' => 'nullable|string|max:255',
            'meta.routing_number' => 'nullable|string|max:255',
            'meta.upi_id' => 'nullable|string|max:255',
            'meta.bank_name' => 'nullable|string|max:255',
            'meta.account_holder' => 'nullable|string|max:255',
            'meta.branch' => 'nullable|string|max:255',
        ]);

        $payment_type = PaymentType::findOrFail($id);

        $forbidden = ['cash', 'cheque'];
        if (in_array(strtolower($request->name), $forbidden)) {
            return response()->json([
                'message' => __('You cannot create a bank account with this name.'),
            ], 422);
        }

        $hasTransactions = $payment_type->transactions()->exists();

        if ($hasTransactions) {
            return response()->json([
                'message' => __("You can't Change opening balance because this bank already has transactions.")
            ], 422);
        }

        $updateData = $request->except('business_id', 'show_in_invoice', 'balance', 'opening_balance', 'meta');

        // Add controlled fields
        $updateData['business_id'] = auth()->user()->business_id;
        $updateData['show_in_invoice'] = $request->show_in_invoice;

        // Only update balances if no transactions exist
        if (!$hasTransactions) {
            $updateData['opening_balance'] = $request->opening_balance ?? 0;
            $updateData['balance'] = $request->opening_balance ?? 0;
        }

        // Build meta explicitly
        $updateData['meta'] = [
            'account_number' => $request->account_number,
            'routing_number' => $request->routing_number,
            'upi_id' => $request->upi_id,
            'bank_name' => $request->bank_name,
            'account_holder' => $request->account_holder,
            'branch' => $request->branch,
        ];

        $payment_type->update($updateData);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $payment_type,
        ]);
    }

    public function destroy($id)
    {
        $paymentType = PaymentType::find($id);

        if (!$paymentType) {
            return response()->json([
                'message' => __('Data not found.'),
                'data' => null,
            ], 404);
        }

        // Check if this payment type is used in any transaction
        $hasTransactions = $paymentType->transactions()->exists();

        $balance = $paymentType->balance ?? 0;

        if ($hasTransactions && $balance != 0) {
            return response()->json([
                'message' => __('Bank can’t be deleted. It has transactions or balance.')
            ], 400);
        }

        $paymentType->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
