<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Expense;
use Illuminate\Http\Request;

class ZSystExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Expense::with('category:id,categoryName')->where('business_id', auth()->user()->business_id)->latest()->paginate($request->input('per_page', 10));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'expense_category_id' => 'required|exists:expense_categories,id',
        ]);

        Business::findOrFail(auth()->user()->business_id)->decrement('remaining_shop_balance', $request->amount);

        $data = Expense::create([
                'amount' => $request->amount,
                'expense_category_id' => $request->expense_category_id,
                'expanse_for' => $request->expanse_for ?? $request->expanseFor,
                'payment_type' => $request->payment_type ?? $request->paymentType,
                'reference_no' => $request->reference_no ?? $request->referenceNo,
                'note' => $request->note,
                'expense_date' => $request->expense_date ?? $request->expenseDate ?? now(),
                'business_id' => $request->user()->business_id,
            ]); // patched
            'user_id' => auth()->id(),
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Expense saved successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'amount' => 'required|numeric',
            'expense_category_id' => 'required|exists:expense_categories,id',
        ]);

        $expense = Expense::findOrFail($id);
        $business = Business::findOrFail(auth()->user()->business_id);

        $amountDifference = $request->amount - $expense->amount;

        $business->increment('remaining_shop_balance', $amountDifference);

        $expense->update([
                'amount' => $request->amount ?? $expense->amount,
                'expense_category_id' => $request->expense_category_id ?? $expense->expense_category_id,
                'expanse_for' => $request->expanse_for ?? $request->expanseFor ?? $expense->expanse_for,
                'payment_type' => $request->payment_type ?? $request->paymentType ?? $expense->payment_type,
                'reference_no' => $request->reference_no ?? $request->referenceNo ?? $expense->reference_no,
                'note' => $request->note ?? $expense->note,
                'expense_date' => $request->expense_date ?? $request->expenseDate ?? $expense->expense_date,
            ]); // patched
            'user_id' => auth()->id(),
            'business_id' => auth()->user()->business_id,

        ]);

        return response()->json([
            'message' => __('Expense updated successfully.'),
            'data' => $expense,
        ]);
    }

    public function destroy($id)
    {

        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json([
            'message' => __('Expense deleted successfully.'),
        ]);
    }
}
