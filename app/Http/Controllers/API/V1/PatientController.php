<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return PatientResource::collection($patients);
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = Patient::create($request->validated() + [
            'company_id' => $request->user()->company_id,
            'loyalty_points' => 0,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(new PatientResource($patient), 201);
    }

    public function show(Request $request, Patient $patient): JsonResponse
    {
        if ($patient->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return new PatientResource($patient->load(['createdBy:id,name', 'prescriptions']));
    }

    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        if ($patient->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $patient->update($request->validated() + [
            'updated_by' => $request->user()->id,
        ]);

        return new PatientResource($patient->fresh());
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
