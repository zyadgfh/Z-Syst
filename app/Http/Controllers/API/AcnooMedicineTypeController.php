<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicineType;
use Illuminate\Http\Request;

class AcnooMedicineTypeController extends Controller
{
    public function index()
    {
        $data = MedicineType::where('business_id', auth()->user()->business_id)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:medicine_types,name,NULL,id,business_id,' . auth()->user()->business_id,
        ]);

        $data = MedicineType::create($request->all() + [
                    'business_id' => auth()->user()->business_id
                ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, MedicineType $medicineType)
    {
        $request->validate([
            'name' => [
                'required',
                'unique:medicine_types,name,' . $medicineType->id . ',id,business_id,' . auth()->user()->business_id,
            ],
        ]);

        $medicineType = $medicineType->update($request->all());

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $medicineType,
        ]);
    }

    public function destroy(MedicineType $medicineType)
    {
        $medicineType->delete();
        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
