<?php

namespace App\Http\Controllers\Api;

use App\Models\Rack;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RackController extends Controller
{
    public function index()
    {
        $data = Rack::with('shelves:id,name')->where('business_id', auth()->user()->business_id)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'shelf_id'     => 'required|array',
            'shelf_id.*'   => 'exists:shelves,id',
            'name'         => 'required|string|max:255',
            'status' => 'required|in:0,1',
        ]);

        $rack = Rack::create($request->except('business_id') + [
            'business_id' => auth()->user()->business_id
        ]);

        $rack->shelves()->sync($request->shelf_id);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $rack,
        ]);
    }


    public function update(Request $request, string $id)
    {
        $rack = Rack::find($id);
        if (!$rack) {
            return response()->json([
                'message' => __('Rack not found.'),
                'data' => null,
            ], 404);
        }

        $request->validate([
            'shelf_id'     => 'required|array',
            'shelf_id.*'   => 'exists:shelves,id',
            'name'         => 'required|string|max:255',
            'status' => 'required|in:0,1',
        ]);

        $rack->update($request->except('business_id') + [
            'business_id' => auth()->user()->business_id
        ]);

        $rack->shelves()->sync($request->shelf_id);

        return response()->json([
            'message' => __('Data updated successfully.'),
            'data' => $rack,
        ]);
    }

    public function destroy(string $id)
    {
        $rack = Rack::find($id);

        if (!$rack) {
            return response()->json([
                'message' => __('Rack not found.'),
            ], 404);
        }

        $rack->delete();
        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
