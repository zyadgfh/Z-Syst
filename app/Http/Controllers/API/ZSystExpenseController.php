<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
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
    public function store(StoreExpenseRequest $request)
    {

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

    public function update(UpdateExpenseRequest $request, $id)
    {

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

    public function destroy($id)
    {

        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json([
            'message' => __('Expense deleted successfully.'),
        ]);
    }
}
