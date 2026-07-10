<?php

namespace App\Http\Controllers\Api;

use App\Models\Shelf;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ShelfController extends Controller
{
    public function index()
    {
        $data = Shelf::where('business_id', auth()->user()->business_id)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:0,1',
        ]);

        $shelf = Shelf::create($request->except('business_id') + [
            'business_id' => auth()->user()->business_id
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $shelf,
        ]);
    }


    public function update(Request $request, string $id)
    {
        $shelf = Shelf::find($id);

        if (!$shelf) {
            return response()->json([
                'message' => __('Shelf not found.'),
                'data' => null,
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:0,1',
        ]);

        $shelf->update($request->except('business_id') + [
            'business_id' => auth()->user()->business_id
        ]);

        return response()->json([
            'message' => __('Data updated successfully.'),
            'data' => $shelf,
        ]);
    }

    public function destroy(string $id)
    {
        $shelf = Shelf::find($id);

        if (!$shelf) {
            return response()->json([
                'message' => __('Shelf not found.'),
            ], 404);
        }

        $shelf->delete();
        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
