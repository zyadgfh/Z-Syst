<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $doctors = Doctor::forCompany($request->user()->company_id)
            ->with(['createdBy:id,name'])
            ->when($request->search, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('name', 'like', "%{$v}%")
                        ->orWhere('phone', 'like', "%{$v}%")
                        ->orWhere('specialization', 'like', "%{$v}%")
                        ->orWhere('license_number', 'like', "%{$v}%");
                });
            })
            ->when($request->specialization, fn($q, $v) => $q->where('specialization', $v))
            ->when($request->is_active, fn($q, $v) => $q->where('is_active', $v === 'true' || $v === '1'))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($doctors);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'license_number' => 'required|string|max:100|unique:doctors,license_number',
            'clinic_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $doctor = Doctor::create($validator->validated() + [
            'company_id' => $request->user()->company_id,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($doctor->load(['createdBy:id,name']), 201);
    }

    public function show(Request $request, Doctor $doctor): JsonResponse
    {
        if ($doctor->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($doctor->load(['createdBy:id,name', 'prescriptions']));
    }

    public function update(Request $request, Doctor $doctor): JsonResponse
    {
        if ($doctor->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'specialization' => 'sometimes|string|max:255',
            'license_number' => 'sometimes|string|max:100|unique:doctors,license_number,' . $doctor->id,
            'clinic_name' => 'nullable|string|max:255',
            'phone' => 'sometimes|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $doctor->update($validator->validated() + [
            'updated_by' => $request->user()->id,
        ]);

        return response()->json($doctor->fresh());
    }

    public function destroy(Request $request, Doctor $doctor): JsonResponse
    {
        if ($doctor->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $doctor->delete();

        return response()->json(['message' => 'Doctor deleted successfully']);
    }
}