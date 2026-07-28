<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manufacturer;
use Illuminate\Http\Request;

class AcnooManufacturerController extends Controller
{
    public function index()
    {
        $data = Manufacturer::where('business_id', auth()->user()->business_id)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $business_id = auth()->user()->business_id;
        $request->validate([
            'name' => 'required|unique:manufacturers,name,NULL,id,business_id,' . $business_id,
            'description' => 'nullable|string'
        ]);

        $data = Manufacturer::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'business_id' => $business_id
                ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, Manufacturer $manufacturer)
    {
        $request->validate([
            'name' => [
                'required',
                'unique:manufacturers,name,' . $manufacturer->id . ',id,business_id,' . auth()->user()->business_id,
            ],
            'description' => 'nullable|string'
        ]);

        $manufacturer = $manufacturer->update([
                    'name' => $request->name,
                    'description' => $request->description,
                    'business_id' => auth()->user()->business_id
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
