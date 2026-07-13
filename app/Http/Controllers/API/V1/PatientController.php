<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $patients = Patient::forCompany($request->user()->company_id)
            ->with(['createdBy:id,name'])
            ->when($request->search, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('name', 'like', "%{$v}%")
                        ->orWhere('phone', 'like', "%{$v}%")
                        ->orWhere('email', 'like', "%{$v}%");
                });
            })
            ->when($request->is_active, fn($q, $v) => $q->where('is_active', $v === 'true' || $v === '1'))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($patients);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'blood_group' => 'nullable|string|max:20',
            'allergies' => 'nullable|json',
            'medical_history' => 'nullable|json',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $patient = Patient::create($validator->validated() + [
            'company_id' => $request->user()->company_id,
            'loyalty_points' => 0,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($patient->load(['createdBy:id,name']), 201);
    }

    public function show(Request $request, Patient $patient): JsonResponse
    {
        if ($patient->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($patient->load(['createdBy:id,name', 'prescriptions']));
    }

    public function update(Request $request, Patient $patient): JsonResponse
    {
        if ($patient->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'blood_group' => 'nullable|string|max:20',
            'allergies' => 'nullable|json',
            'medical_history' => 'nullable|json',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $patient->update($validator->validated() + [
            'updated_by' => $request->user()->id,
        ]);

        return response()->json($patient->fresh());
    }

    public function destroy(Request $request, Patient $patient): JsonResponse
    {
        if ($patient->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $patient->delete();

        return response()->json(['message' => 'Patient deleted successfully']);
    }
}