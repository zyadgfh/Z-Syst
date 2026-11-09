<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = ExpenseCategory::where('business_id', auth()->user()->business_id)->latest()->get();

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
            'categoryName' => 'required|unique:expense_categories,categoryName,NULL,id,business_id,'.auth()->user()->business_id,
        ]);

        $data = ExpenseCategory::create([
                'categoryName' => $request->categoryName,
                'categoryDescription' => $request->categoryDescription,
                'business_id' => $request->user()->business_id,
                'status' => 1,
            ]); // patched
            'business_id' => auth()->user()->business_id,
            'status' => $request->status == 'true' ? 1 : 0,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $category = ExpenseCategory::findOrFail($id);

        $request->validate([
            'categoryName' => [
                'required',
                'unique:expense_categories,categoryName,'.$category->id.',id,business_id,'.auth()->user()->business_id,
            ],
        ]);

        $category->update([
                'categoryName' => $request->categoryName,
                'categoryDescription' => $request->categoryDescription,
            ]); // patched
            'business_id' => auth()->user()->business_id,
            'status' => $request->status == 'true' ? 1 : 0,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = ExpenseCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
