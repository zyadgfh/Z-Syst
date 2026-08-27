<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBoxSizeRequest;
use App\Http\Requests\UpdateBoxSizeRequest;
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

    public function store(StoreBoxSizeRequest $request)
    {

        $data = BoxSize::create($request->all() + [
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    public function update(UpdateBoxSizeRequest $request, BoxSize $boxSize)
    {

        $boxSize = $boxSize->update($request->all());

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
