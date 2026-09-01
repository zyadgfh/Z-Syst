<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInsuranceClaimRequest;
use App\Http\Requests\StoreInsuranceCompanyRequest;
use App\Http\Requests\StoreInsuranceCoverageRequest;
use App\Http\Requests\StoreInsurancePolicyRequest;
use App\Http\Resources\InsuranceClaimResource;
use App\Http\Resources\InsuranceCompanyResource;
use App\Http\Resources\InsuranceCoverageResource;
use App\Http\Resources\InsurancePolicyResource;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Models\InsurancePolicy;
use App\Services\InsuranceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InsuranceController extends Controller
{
    protected InsuranceService $insurance;

    public function __construct(InsuranceService $insurance)
    {
        $this->insurance = $insurance;
    }

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function summary(): JsonResponse
    {
        $businessId = Auth::user()->business_id;

        return response()->json([
            'message' => __('Insurance summary fetched successfully.'),
            'data' => $this->insurance->getSummary($businessId),
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    // ─── Companies ────────────────────────────────────────────────────────────

    public function companiesIndex(Request $request): JsonResponse
    {
        $businessId = Auth::user()->business_id;

        $companies = InsuranceCompany::byBusiness($businessId)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            }))
            ->withCount(['policies', 'claims'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        $companyData = $companies->getCollection()
            ->map(fn ($company) => (new InsuranceCompanyResource($company))->resolve())
            ->values()
            ->all();

        return response()->json([
            'message' => __('Insurance companies fetched successfully.'),
            'companies' => [
                'data' => $companyData,
            ],
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
        ]);
    }

    public function companiesStore(StoreInsuranceCompanyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['business_id'] = Auth::user()->business_id;

        $company = InsuranceCompany::create($data);

        return response()->json([
            'message' => __('Insurance company created successfully.'),
            'company' => new InsuranceCompanyResource($company),
        ], 201);
    }

    public function companiesShow(InsuranceCompany $company): JsonResponse
    {
        $this->authorize('view', $company);

        $company->load(['policies', 'claims']);

        return response()->json([
            'message' => __('Insurance company fetched successfully.'),
            'company' => new InsuranceCompanyResource($company),
        ]);
    }

    public function companiesUpdate(StoreInsuranceCompanyRequest $request, InsuranceCompany $company): JsonResponse
    {
        $this->authorize('update', $company);

        $company->update($request->validated());

        return response()->json([
            'message' => __('Insurance company updated successfully.'),
            'company' => new InsuranceCompanyResource($company->fresh()),
        ]);
    }

    public function companiesDestroy(InsuranceCompany $company): JsonResponse
    {
        $this->authorize('delete', $company);
        $company->delete();

        return response()->json(['message' => __('Insurance company deleted successfully.')]);
    }

    // ─── Policies ─────────────────────────────────────────────────────────────

    public function policiesIndex(Request $request): JsonResponse
    {
        $businessId = Auth::user()->business_id;

        $policies = InsurancePolicy::byBusiness($businessId)
            ->with(['insuranceCompany:id,name,code', 'customer:id,name'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->insurance_company_id, fn ($q, $id) => $q->where('insurance_company_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('policy_number', 'like', "%{$s}%")
                    ->orWhere('holder_name', 'like', "%{$s}%")
                    ->orWhere('member_id', 'like', "%{$s}%")
                    ->orWhere('card_number', 'like', "%{$s}%");
            }))
            ->when($request->expiring_soon, function ($q) {
                $days = (int) $request->expiring_soon;
                $q->expiringSoon($days);
            })
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'message' => __('Insurance policies fetched successfully.'),
            'policies' => InsurancePolicyResource::collection($policies),
            'meta' => [
                'current_page' => $policies->currentPage(),
                'last_page' => $policies->lastPage(),
                'per_page' => $policies->perPage(),
                'total' => $policies->total(),
            ],
        ]);
    }

    public function policiesStore(StoreInsurancePolicyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['business_id'] = Auth::user()->business_id;

        if (empty($data['policy_number'])) {
            $data['policy_number'] = $this->insurance->generatePolicyNumber($data['business_id']);
        }

        $policy = InsurancePolicy::create($data);

        return response()->json([
            'message' => __('Insurance policy created successfully.'),
            'policy' => new InsurancePolicyResource($policy),
        ], 201);
    }

    public function policiesShow(InsurancePolicy $policy): JsonResponse
    {
        $this->authorize('view', $policy);

        $policy->load(['insuranceCompany', 'customer', 'claims']);

        return response()->json([
            'message' => __('Insurance policy fetched successfully.'),
            'policy' => new InsurancePolicyResource($policy),
        ]);
    }

    public function policiesUpdate(StoreInsurancePolicyRequest $request, InsurancePolicy $policy): JsonResponse
    {
        $this->authorize('update', $policy);
        $policy->update($request->validated());

        return response()->json([
            'message' => __('Insurance policy updated successfully.'),
            'policy' => new InsurancePolicyResource($policy->fresh()),
        ]);
    }

    public function policiesDestroy(InsurancePolicy $policy): JsonResponse
    {
        $this->authorize('delete', $policy);
        $policy->delete();

        return response()->json(['message' => __('Insurance policy deleted successfully.')]);
    }

    // ─── Claims ───────────────────────────────────────────────────────────────

    public function claimsIndex(Request $request): JsonResponse
    {
        $businessId = Auth::user()->business_id;

        $claims = InsuranceClaim::byBusiness($businessId)
            ->with(['insuranceCompany:id,name', 'insurancePolicy:id,policy_number,holder_name', 'customer:id,name'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->insurance_company_id, fn ($q, $id) => $q->where('insurance_company_id', $id))
            ->when($request->insurance_policy_id, fn ($q, $id) => $q->where('insurance_policy_id', $id))
            ->when($request->from, fn ($q, $d) => $q->whereDate('service_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('service_date', '<=', $d))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('claim_number', 'like', "%{$s}%")
                    ->orWhere('external_reference', 'like', "%{$s}%");
            }))
            ->latest('service_date')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'message' => __('Insurance claims fetched successfully.'),
            'claims' => InsuranceClaimResource::collection($claims),
            'meta' => [
                'current_page' => $claims->currentPage(),
                'last_page' => $claims->lastPage(),
                'per_page' => $claims->perPage(),
                'total' => $claims->total(),
            ],
        ]);
    }

    public function claimsStore(StoreInsuranceClaimRequest $request): JsonResponse
    {
        $businessId = Auth::user()->business_id;
        $data = $request->validated();
        $data['business_id'] = $businessId;
        $data['user_id'] = Auth::id();
        $data['claim_number'] = $this->insurance->generateClaimNumber($businessId);
        $data['status'] = 'draft';

        $policy = InsurancePolicy::byBusiness($businessId)->findOrFail($data['insurance_policy_id']);
        $data['insurance_company_id'] = $policy->insurance_company_id;
        $data['customer_id'] = $data['customer_id'] ?? $policy->customer_id;

        $total = (float) $data['total_amount'];
        $coverage = $this->insurance->resolveCoverage($policy, $total);
        $data['covered_amount'] = round($total * ($coverage / 100), 2);
        $data['patient_responsibility'] = round($total - (float) $data['covered_amount'], 2);

        $claim = InsuranceClaim::create($data);

        return response()->json([
            'message' => __('Insurance claim created successfully.'),
            'claim' => new InsuranceClaimResource($claim),
        ], 201);
    }

    public function claimsShow(InsuranceClaim $claim): JsonResponse
    {
        $this->authorize('view', $claim);

        $claim->load(['insuranceCompany', 'insurancePolicy', 'customer', 'sale', 'prescription']);

        return response()->json([
            'message' => __('Insurance claim fetched successfully.'),
            'claim' => new InsuranceClaimResource($claim),
        ]);
    }

    public function claimsSubmit(InsuranceClaim $claim): JsonResponse
    {
        $this->authorize('update', $claim);
        $claim = $this->insurance->submitClaim($claim);

        return response()->json([
            'message' => __('Claim submitted successfully.'),
            'claim' => new InsuranceClaimResource($claim),
        ]);
    }

    public function claimsApprove(Request $request, InsuranceClaim $claim): JsonResponse
    {
        $this->authorize('update', $claim);

        $data = $request->validate([
            'approved_amount' => 'required|numeric|min:0',
            'external_reference' => 'nullable|string|max:100',
        ]);

        $claim = $this->insurance->recordApproval(
            $claim,
            (float) $data['approved_amount'],
            $data['external_reference'] ?? null
        );

        return response()->json([
            'message' => __('Claim approval recorded.'),
            'claim' => new InsuranceClaimResource($claim),
        ]);
    }

    public function claimsReject(Request $request, InsuranceClaim $claim): JsonResponse
    {
        $this->authorize('update', $claim);

        $data = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $claim = $this->insurance->rejectClaim($claim, $data['rejection_reason']);

        return response()->json([
            'message' => __('Claim rejected.'),
            'claim' => new InsuranceClaimResource($claim),
        ]);
    }

    public function claimsPay(Request $request, InsuranceClaim $claim): JsonResponse
    {
        $this->authorize('update', $claim);

        $data = $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'settlement_date' => 'nullable|date',
        ]);

        $claim = $this->insurance->recordPayment(
            $claim,
            (float) $data['paid_amount'],
            isset($data['settlement_date']) ? Carbon::parse($data['settlement_date']) : null
        );

        return response()->json([
            'message' => __('Claim payment recorded.'),
            'claim' => new InsuranceClaimResource($claim),
        ]);
    }

    // ─── Coverage Rules ───────────────────────────────────────────────────────

    public function coveragesIndex(Request $request): JsonResponse
    {
        $businessId = Auth::user()->business_id;

        $coverages = InsuranceCoverage::byBusiness($businessId)
            ->with(['insuranceCompany:id,name', 'product:id,name', 'category:id,name'])
            ->when($request->insurance_company_id, fn ($q, $id) => $q->where('insurance_company_id', $id))
            ->when($request->is_active !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->scope, fn ($q, $s) => $q->where('scope', $s))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'message' => __('Coverage rules fetched successfully.'),
            'coverages' => InsuranceCoverageResource::collection($coverages),
            'meta' => [
                'current_page' => $coverages->currentPage(),
                'last_page' => $coverages->lastPage(),
                'per_page' => $coverages->perPage(),
                'total' => $coverages->total(),
            ],
        ]);
    }

    public function coveragesStore(StoreInsuranceCoverageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['business_id'] = Auth::user()->business_id;
        $data['scope'] = $data['scope'] ?? ($data['product_id'] ? 'product' : ($data['category_id'] ? 'category' : 'all'));

        $coverage = InsuranceCoverage::create($data);

        return response()->json([
            'message' => __('Coverage rule created successfully.'),
            'coverage' => new InsuranceCoverageResource($coverage),
        ], 201);
    }

    public function coveragesUpdate(StoreInsuranceCoverageRequest $request, InsuranceCoverage $coverage): JsonResponse
    {
        $this->authorize('update', $coverage);
        $coverage->update($request->validated());

        return response()->json([
            'message' => __('Coverage rule updated successfully.'),
            'coverage' => new InsuranceCoverageResource($coverage->fresh()),
        ]);
    }

    public function coveragesDestroy(InsuranceCoverage $coverage): JsonResponse
    {
        $this->authorize('delete', $coverage);
        $coverage->delete();

        return response()->json(['message' => __('Coverage rule deleted successfully.')]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
}
