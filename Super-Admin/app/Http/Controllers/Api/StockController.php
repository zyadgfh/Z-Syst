<?php

namespace App\Http\Controllers\Api;

use App\Models\Stock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StockController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'productStock' => 'required|integer',
            'stock_id' => 'required|exists:stocks,id'
        ]);

        Stock::where('id', $request->stock_id)->increment('productStock', $request->productStock);

        return response()->json([
            'message' => __('Data saved successfully.'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Stock::where('id', $id)->update($request->except('_method'));

        return response()->json([
            'message' => __('Data saved successfully.'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Stock::where('id', $id)->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
