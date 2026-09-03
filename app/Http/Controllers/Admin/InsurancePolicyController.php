<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;
use App\Services\InsuranceService;
use Illuminate\Http\Request;

class InsurancePolicyController extends Controller
{
    protected InsuranceService $insuranceService;

    public function __construct(InsuranceService $insuranceService)
    {
        $this->insuranceService = $insuranceService;
        $this->middleware('permission:insurance-policies-create')->only('create', 'store');
        $this->middleware('permission:insurance-policies-read')->only('index', 'show');
        $this->middleware('permission:insurance-policies-update')->only('edit', 'update');
        $this->middleware('permission:insurance-policies-delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $policies = InsurancePolicy::with(['company:id,name', 'business:id,companyName', 'customer'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('policy_number', 'like', '%'.$request->search.'%')
                    ->orWhere('holder_name', 'like', '%'.$request->search.'%')
                    ->orWhere('member_id', 'like', '%'.$request->search.'%');
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(10);

        return view('admin.insurance.policies.index', compact('policies'));
    }

    public function create()
    {
        $companies = InsuranceCompany::active()->get();

        return view('admin.insurance.policies.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'customer_id' => 'nullable|exists:parties,id',
            'member_id' => 'nullable|string|max:255',
            'card_number' => 'nullable|string|max:255',
            'holder_name' => 'required|string|max:255',
            'holder_dob' => 'nullable|date',
            'holder_gender' => 'nullable|in:male,female,other',
            'holder_phone' => 'nullable|string|max:50',
            'holder_email' => 'nullable|email',
            'holder_address' => 'nullable|string',
            'plan_type' => 'required|in:individual,family,corporate,government',
            'status' => 'required|in:active,expired,suspended,cancelled,pending',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'annual_limit' => 'nullable|numeric|min:0',
            'coverage_percent' => 'nullable|numeric|min:0|max:100',
            'copay_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $policy = $this->insuranceService->createPolicy($request->all());

            return response()->json([
                'message' => __('Insurance policy created successfully'),
                'redirect' => route('admin.insurance.policies.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error creating insurance policy: ').$e->getMessage(),
            ], 500);
        }
    }

    public function show(InsurancePolicy $policy)
    {
        $policy->load(['company', 'business', 'customer', 'claims']);

        return view('admin.insurance.policies.show', compact('policy'));
    }

    public function edit(InsurancePolicy $policy)
    {
        $companies = InsuranceCompany::active()->get();

        return view('admin.insurance.policies.edit', compact('policy', 'companies'));
    }

    public function update(Request $request, InsurancePolicy $policy)
    {
        $request->validate([
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'customer_id' => 'nullable|exists:parties,id',
            'member_id' => 'nullable|string|max:255',
            'card_number' => 'nullable|string|max:255',
            'holder_name' => 'required|string|max:255',
            'holder_dob' => 'nullable|date',
            'holder_gender' => 'nullable|in:male,female,other',
            'holder_phone' => 'nullable|string|max:50',
            'holder_email' => 'nullable|email',
            'holder_address' => 'nullable|string',
            'plan_type' => 'required|in:individual,family,corporate,government',
            'status' => 'required|in:active,expired,suspended,cancelled,pending',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'annual_limit' => 'nullable|numeric|min:0',
            'coverage_percent' => 'nullable|numeric|min:0|max:100',
            'copay_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $policy = $this->insuranceService->updatePolicy($policy, $request->all());

            return response()->json([
                'message' => __('Insurance policy updated successfully'),
                'redirect' => route('admin.insurance.policies.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error updating insurance policy: ').$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(InsurancePolicy $policy)
    {
        try {
            $policy->delete();

            return response()->json([
                'message' => __('Insurance policy deleted successfully'),
                'redirect' => route('admin.insurance.policies.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error deleting insurance policy: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check policy eligibility
     */
    public function checkEligibility(Request $request, InsurancePolicy $policy)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $eligibility = $this->insuranceService->validatePolicyEligibility($policy, $request->amount);

        return response()->json($eligibility);
    }
}
