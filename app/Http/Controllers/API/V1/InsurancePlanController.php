<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Models\InsurancePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InsurancePlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $plans = InsurancePlan::whereHas('insuranceCompany', function ($q) use ($request) {
            $q->where('company_id', $request->user()->company_id);
        })
        ->with(['insuranceCompany:id,name'])
        ->when($request->insurance_company_id, fn($q, $v) => $q->where('insurance_company_id', $v))
        ->when($request->is_active, fn($q, $v) => $q->where('is_active', $v === 'true' || $v === '1'))
        ->orderByDesc('created_at')
        ->paginate($request->per_page ?? 25);

        return response()->json($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'plan_name' => 'required|string|max:255',
            'coverage_percentage' => 'required|numeric|min:0|max:100',
            'max_coverage' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        // Verify insurance company belongs to user's company
        $insuranceCompany = InsuranceCompany::findOrFail($request->insurance_company_id);
        if ($insuranceCompany->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $plan = InsurancePlan::create($validator->validated() + [
            'is_active' => true,
        ]);

        return response()->json($plan->load(['insuranceCompany:id,name']), 201);
    }

    public function show(Request $request, InsurancePlan $insurancePlan): JsonResponse
    {
        if ($insurancePlan->insuranceCompany->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($insurancePlan->load(['insuranceCompany:id,name']));
    }

    public function update(Request $request, InsurancePlan $insurancePlan): JsonResponse
    {
        if ($insurancePlan->insuranceCompany->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'plan_name' => 'sometimes|string|max:255',
            'coverage_percentage' => 'sometimes|numeric|min:0|max:100',
            'max_coverage' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $insurancePlan->update($validator->validated());

        return response()->json($insurancePlan->fresh());
    }

    public function destroy(Request $request, InsurancePlan $insurancePlan): JsonResponse
    {
        if ($insurancePlan->insuranceCompany->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $insurancePlan->delete();

        return response()->json(['message' => 'Insurance plan deleted successfully']);
    }
}