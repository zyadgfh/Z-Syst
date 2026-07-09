<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\InsuranceClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InsuranceClaimController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        Log::info('InsuranceClaimController@index called', ['user' => $request->user()?->id, 'company' => $request->user()?->company_id, 'tenant' => app()->bound('tenant.company_id') ? app('tenant.company_id') : null]);

        $companyId = $request->user()?->company_id ?? (app()->bound('tenant.company_id') ? app('tenant.company_id') : $request->header('X-Company-Id'));

        $claims = InsuranceClaim::forCompany($companyId)
            ->with(['patient', 'insuranceCompany', 'insurancePlan', 'sale', 'branch'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->insurance_company_id, fn($q, $v) => $q->where('insurance_company_id', $v))
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->from_date, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->to_date, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15);

        return $this->success($claims);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|exists:sales,id',
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'insurance_plan_id' => 'nullable|exists:insurance_plans,id',
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'amount_claimed' => 'required|numeric|min:0.01',
            'co_pay_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $claim = InsuranceClaim::create(array_merge(
            $validator->validated(),
            [
                'company_id' => $request->user()->company_id,
                'claim_number' => 'CLAIM-' . strtoupper(Str::random(8)),
                'status' => 'pending',
                'submitted_by' => $request->user()->id,
            ]
        ));

        return $this->created($claim->load(['patient', 'insuranceCompany', 'insurancePlan', 'sale']), 'Insurance claim created successfully');
    }

    public function show(Request $request, InsuranceClaim $insuranceClaim): JsonResponse
    {
        if ($insuranceClaim->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        return $this->success($insuranceClaim->load(['patient', 'insuranceCompany', 'insurancePlan', 'sale', 'branch', 'submittedBy', 'approvedBy']));
    }

    public function update(Request $request, InsuranceClaim $insuranceClaim): JsonResponse
    {
        if ($insuranceClaim->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        if (!in_array($insuranceClaim->status, ['pending', 'submitted'])) {
            return $this->error('Cannot modify a processed claim', 422);
        }

        $validator = Validator::make($request->all(), [
            'insurance_plan_id' => 'nullable|exists:insurance_plans,id',
            'amount_claimed' => 'sometimes|numeric|min:0.01',
            'co_pay_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $insuranceClaim->update($validator->validated());
        return $this->success($insuranceClaim, 'Insurance claim updated successfully');
    }

    public function destroy(Request $request, InsuranceClaim $insuranceClaim): JsonResponse
    {
        if ($insuranceClaim->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        if (!in_array($insuranceClaim->status, ['pending', 'submitted'])) {
            return $this->error('Cannot delete a processed claim', 422);
        }

        $insuranceClaim->delete();
        return $this->success(null, 'Insurance claim deleted successfully');
    }

    public function submit(Request $request, InsuranceClaim $insuranceClaim): JsonResponse
    {
        if ($insuranceClaim->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        if ($insuranceClaim->status !== 'pending') {
            return $this->error('Only pending claims can be submitted', 422);
        }

        $insuranceClaim->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return $this->success($insuranceClaim, 'Insurance claim submitted successfully');
    }

    public function approve(Request $request, InsuranceClaim $insuranceClaim): JsonResponse
    {
        if ($insuranceClaim->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        if (!in_array($insuranceClaim->status, ['submitted', 'pending'])) {
            return $this->error('Only submitted claims can be approved', 422);
        }

        $validator = Validator::make($request->all(), [
            'amount_approved' => 'required|numeric|min:0',
            'settlement_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $insuranceClaim->update(array_merge(
            $validator->validated(),
            [
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]
        ));

        return $this->success($insuranceClaim, 'Insurance claim approved successfully');
    }

    public function reject(Request $request, InsuranceClaim $insuranceClaim): JsonResponse
    {
        if ($insuranceClaim->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        if (!in_array($insuranceClaim->status, ['submitted', 'pending'])) {
            return $this->error('Only submitted claims can be rejected', 422);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $insuranceClaim->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return $this->success($insuranceClaim, 'Insurance claim rejected successfully');
    }
}