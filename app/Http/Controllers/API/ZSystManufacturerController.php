<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManufacturerRequest;
use App\Http\Requests\UpdateManufacturerRequest;
use App\Models\Manufacturer;
use Illuminate\Http\Request;

class ZSystManufacturerController extends Controller
{
    public function index()
    {
        $data = Manufacturer::where('business_id', auth()->user()->business_id)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(StoreManufacturerRequest $request)
    {

        $data = Manufacturer::create([
            'name' => $request->name,
            'description' => $request->description,
            'business_id' => $business_id,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    public function update(UpdateManufacturerRequest $request, Manufacturer $manufacturer)
    {

        $manufacturer = $manufacturer->update([
            'name' => $request->name,
            'description' => $request->description,
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Data updated successfully.'),
            'data' => $manufacturer,
        ]);
    }

    public function destroy(Manufacturer $manufacturer)
    {
        $manufacturer->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
