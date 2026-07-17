<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return DoctorResource::collection($doctors);
    }

    public function store(StoreDoctorRequest $request): JsonResponse
    {
        $doctor = Doctor::create($request->validated() + [
            'company_id' => $request->user()->company_id,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(new DoctorResource($doctor), 201);
    }

    public function show(Request $request, Doctor $doctor): JsonResponse
    {
        if ($doctor->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return new DoctorResource($doctor->load(['createdBy:id,name', 'prescriptions']));
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): JsonResponse
    {
        if ($doctor->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $doctor->update($request->validated() + [
            'updated_by' => $request->user()->id,
        ]);

        return new DoctorResource($doctor->fresh());
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
