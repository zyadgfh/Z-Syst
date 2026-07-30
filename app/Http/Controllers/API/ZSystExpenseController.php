<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use App\Models\Business;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ZSystExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Expense::with('category:id,categoryName')->where('business_id', auth()->user()->business_id)->latest()->paginate(10);

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

        Business::findOrFail(auth()->user()->business_id)->decrement('remainingShopBalance', $request->amount);

        $data = Expense::create($request->except('status') + [
                    'user_id' => auth()->id(),
                    'business_id' => auth()->user()->business_id,
                ]);

        return response()->json([
            'message' => __('Expense saved successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, $id) {

        $request->validate([
            'amount' => 'required|numeric',
            'expense_category_id' => 'required|exists:expense_categories,id',
        ]);

        $expense = Expense::findOrFail($id);
        $business = Business::findOrFail(auth()->user()->business_id);

        $amountDifference = $request->amount - $expense->amount;

        $business->increment('remainingShopBalance', $amountDifference);


        $expense->update($request->except('user_id', 'business_id') + [
            'user_id' => auth()->id(),
            'business_id' => auth()->user()->business_id,

        ]);

        return response()->json([
            'message' => __('Expense updated successfully.'),
            'data' => $expense,
        ]);
    }

    public function destroy($id) {

        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json([
            'message' => __('Expense deleted successfully.'),
        ]);
    }
}
