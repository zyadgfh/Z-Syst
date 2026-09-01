<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicineTypeRequest;
use App\Http\Requests\UpdateMedicineTypeRequest;
use App\Models\MedicineType;
use Illuminate\Http\Request;

class ZSystMedicineTypeController extends Controller
{
    public function index()
    {
        $data = MedicineType::where('business_id', auth()->user()->business_id)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(StoreMedicineTypeRequest $request)
    {

        $data = MedicineType::create($request->all() + [
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    public function update(UpdateMedicineTypeRequest $request, MedicineType $medicineType)
    {

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
