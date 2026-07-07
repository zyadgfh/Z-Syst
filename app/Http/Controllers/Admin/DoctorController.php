<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $doctors = Doctor::forCompany($request->user()->company_id)
            ->when($request->search, fn($q, $v) => $q->where(function($q) use ($v) {
                $q->where('name', 'like', "%{$v}%")
                  ->orWhere('specialization', 'like', "%{$v}%")
                  ->orWhere('license_number', 'like', "%{$v}%");
            }))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return $this->success($doctors);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100|unique:doctors,license_number',
            'clinic_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $doctor = Doctor::create(array_merge(
            $validator->validated(),
            ['company_id' => $request->user()->company_id, 'created_by' => $request->user()->id]
        ));

        return $this->created($doctor, 'Doctor created successfully');
    }

    public function show(Request $request, Doctor $doctor): JsonResponse
    {
        if ($doctor->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        return $this->success($doctor->load('prescriptions'));
    }

    public function update(Request $request, Doctor $doctor): JsonResponse
    {
        if ($doctor->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100|unique:doctors,license_number,' . $doctor->id,
            'clinic_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $doctor->update(array_merge($validator->validated(), ['updated_by' => $request->user()->id]));
        return $this->success($doctor, 'Doctor updated successfully');
    }

    public function destroy(Request $request, Doctor $doctor): JsonResponse
    {
        if ($doctor->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        $doctor->delete();
        return $this->success(null, 'Doctor deleted successfully');
    }
}