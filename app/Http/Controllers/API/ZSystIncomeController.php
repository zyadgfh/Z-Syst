<?php

namespace App\Http\Controllers\Api;

use App\Models\Income;
use App\Models\Business;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ZSystIncomeController extends Controller
{
    public function index()
    {
        $data = Income::with('category:id,categoryName')->where('business_id', auth()->user()->business_id)->latest()->paginate(10);

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
            'income_category_id' => 'required|integer|exists:income_categories,id',
        ]);

        Business::findOrFail(auth()->user()->business_id)->decrement('remainingShopBalance', $request->amount);

        $data = Income::create($request->except('user_id', 'business_id') + [
                    'user_id' => auth()->id(),
                    'business_id' => auth()->user()->business_id,
                ]);

        return response()->json([
            'message' => __('Income saved successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'income_category_id' => 'required|integer|exists:income_categories,id',
        ]);

        $income = Income::findOrFail($id);
        $business = Business::findOrFail(auth()->user()->business_id);

        $amountDifference = $request->amount - $income->amount;

        $business->increment('remainingShopBalance', $amountDifference);

        $income->update($request->except('user_id', 'business_id') + [
            'user_id' => auth()->id(),
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Income updated successfully.'),
            'data' => $income,
        ]);
    }

    public function destroy($id) {

        $income = Income::findOrFail($id);
        $income->delete();

        return response()->json([
            'message' => __('Income deleted successfully.'),
        ]);
    }
}
