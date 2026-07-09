<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\API\BaseController;
use App\Models\InsurancePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InsurancePlanController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $plans = InsurancePlan::forCompany($request->user()->company_id)
            ->with('insuranceCompany')
            ->when($request->insurance_company_id, fn ($q, $v) => $q->where('insurance_company_id', $v))
            ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return $this->success($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'name' => 'required|string|max:255',
            'coverage_percentage' => 'required|numeric|min:0|max:100',
            'max_coverage' => 'nullable|numeric|min:0',
            'annual_limit' => 'nullable|numeric|min:0',
            'co_pay' => 'numeric|min:0|max:100',
            'requires_pre_approval' => 'boolean',
            'covered_items' => 'nullable|string',
            'exclusions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $data = $validator->validated();
        $data['code'] = strtoupper(Str::slug($data['name'])).'-'.Str::random(4);
        $data['company_id'] = $request->user()->company_id;

        $plan = InsurancePlan::create($data);

        return $this->created($plan->load('insuranceCompany'), 'Insurance plan created successfully');
    }

    public function show(Request $request, InsurancePlan $insurancePlan): JsonResponse
    {
        if ($insurancePlan->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        return $this->success($insurancePlan->load('insuranceCompany'));
    }

    public function update(Request $request, InsurancePlan $insurancePlan): JsonResponse
    {
        if ($insurancePlan->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'coverage_percentage' => 'numeric|min:0|max:100',
            'max_coverage' => 'nullable|numeric|min:0',
            'annual_limit' => 'nullable|numeric|min:0',
            'co_pay' => 'numeric|min:0|max:100',
            'requires_pre_approval' => 'boolean',
            'covered_items' => 'nullable|string',
            'exclusions' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $insurancePlan->update($validator->validated());

        return $this->success($insurancePlan, 'Insurance plan updated successfully');
    }

    public function destroy(Request $request, InsurancePlan $insurancePlan): JsonResponse
    {
        if ($insurancePlan->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        $insurancePlan->delete();

        return $this->success(null, 'Insurance plan deleted successfully');
    }
}
