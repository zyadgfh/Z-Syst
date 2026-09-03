<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncomeCategoryRequest;
use App\Http\Requests\UpdateIncomeCategoryRequest;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;

class ZSystIncomeCategoryController extends Controller
{
    public function index()
    {
        $data = IncomeCategory::where('business_id', auth()->user()->business_id)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIncomeCategoryRequest $request)
    {

        $data = IncomeCategory::create($request->except('status') + [
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
    public function update(UpdateIncomeCategoryRequest $request, $id)
    {
        $category = IncomeCategory::findOrFail($id);

        $category->update($request->except('status') + [
            'business_id' => auth()->user()->business_id,
            'status' => $request->status == 'true' ? 1 : 0,
        ]);

        return response()->json([
            'message' => __('Data updated successfully.'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = IncomeCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
