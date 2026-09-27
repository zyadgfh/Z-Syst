<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoxSize;
use Illuminate\Http\Request;

class ZSystBoxSizeController extends Controller
{
    public function index()
    {
        $data = BoxSize::where('business_id', auth()->user()->business_id)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:box_sizes,name,NULL,id,business_id,'.auth()->user()->business_id,
        ]);

        $data = BoxSize::create([
            'business_id' => auth()->user()->business_id,
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, BoxSize $boxSize)
    {
        $request->validate([
            'name' => [
                'required',
                'unique:box_sizes,name,'.$boxSize->id.',id,business_id,'.auth()->user()->business_id,
            ],
        ]);

        $boxSize->update([
            'name' => $request->name,
        ]);

        $boxSize = $boxSize->fresh();

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $boxSize,
        ]);
    }

    public function destroy(BoxSize $boxSize)
    {
        $boxSize->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
