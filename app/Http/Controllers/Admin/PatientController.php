<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\API\BaseController;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $patients = Patient::forCompany($request->user()->company_id)
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('name', 'like', "%{$v}%")
                    ->orWhere('phone', 'like', "%{$v}%")
                    ->orWhere('email', 'like', "%{$v}%");
            }))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return $this->success($patients);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'blood_group' => 'nullable|string|max:10',
            'allergies' => 'nullable|array',
            'medical_history' => 'nullable|array',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $patient = Patient::create(array_merge(
            $validator->validated(),
            ['company_id' => $request->user()->company_id, 'created_by' => $request->user()->id]
        ));

        return $this->created($patient, 'Patient created successfully');
    }

    public function show(Request $request, Patient $patient): JsonResponse
    {
        if ($patient->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        return $this->success($patient->load('prescriptions'));
    }

    public function update(Request $request, Patient $patient): JsonResponse
    {
        if ($patient->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'blood_group' => 'nullable|string|max:10',
            'allergies' => 'nullable|array',
            'medical_history' => 'nullable|array',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $patient->update(array_merge($validator->validated(), ['updated_by' => $request->user()->id]));

        return $this->success($patient, 'Patient updated successfully');
    }

    public function destroy(Request $request, Patient $patient): JsonResponse
    {
        if ($patient->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        $patient->delete();

        return $this->success(null, 'Patient deleted successfully');
    }
}
