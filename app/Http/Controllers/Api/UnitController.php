<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Unit::where('business_id', auth()->user()->business_id)->latest()->get();

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
        // Same reasoning as ZSystCategoryController: the global shim skips
        // camelCase names that are still real columns on other tables.
        if ($request->filled('unitName') && ! $request->has('unit_name')) {
            $request->merge(['unit_name' => $request->input('unitName')]);
        }

        $request->validate([
            'unit_name' => 'required|unique:units,unit_name,NULL,id,business_id,'.auth()->user()->business_id,
        ]);

        $data = Unit::create($request->only([
        'unit_name',
        'status'
    ]) + [
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        if ($request->filled('unitName') && ! $request->has('unit_name')) {
            $request->merge(['unit_name' => $request->input('unitName')]);
        }

        $request->validate([
            'unit_name' => [
                'required',
                'unique:units,unit_name,'.$unit->id.',id,business_id,'.auth()->user()->business_id,
            ],
        ]);

        $unit = $unit->update($request->only([
        'unit_name',
        'status'
    ]));

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $unit,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
