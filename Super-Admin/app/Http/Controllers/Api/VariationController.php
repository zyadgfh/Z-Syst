<?php

namespace App\Http\Controllers\Api;

use App\Models\Variation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VariationController extends Controller
{

    public function index()
    {
        $data = Variation::where('business_id', auth()->user()->business_id)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'values' => 'required|string',
            'status' => 'required|in:0,1',
        ]);

        $valuesArray = array_map('trim', explode(',', $request->values));

        $data = Variation::create([
            'name' => $request->name,
            'status' => $request->status,
            'values' => $valuesArray,
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $variation = Variation::find($id);
        if (!$variation) {
            return response()->json([
                'message' => __('Variation not found.'),
                'data' => null,
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'values' => 'required|string',
            'status' => 'required|in:0,1',
        ]);

        $valuesArray = array_map('trim', explode(',', $request->values));

        $variation->update([
            'name' => $request->name,
            'status' => $request->status,
            'values' => $valuesArray,
        ]);

        return response()->json([
            'message' => __('Data updated successfully.'),
            'data' => $variation,
        ]);
    }

    public function destroy(string $id)
    {
        $variation = Variation::find($id);

        if (!$variation) {
            return response()->json([
                'message' => __('Variation not found.'),
            ], 404);
        }

        $variation->delete();
        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
