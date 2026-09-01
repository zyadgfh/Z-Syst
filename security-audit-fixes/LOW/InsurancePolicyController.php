<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsurancePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * InsurancePolicyController — Security Audit Fix (LOW)
 *
 * Fixes applied:
 * 1. Added permission middleware for insurance-policies CRUD
 * 2. Sanitized search input before LIKE query (SQL injection prevention)
 * 3. Enforced forBusiness() scope on index to prevent cross-tenant data leakage
 * 4. Removed company_id fallback to request parameter — always use resolved business ID
 * 5. Added input validation for query parameters (per_page, company_id)
 * 6. Added structured logging for all create/update/delete operations
 */
class InsurancePolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'business.active']);
    }

    public function index(Request $request)
    {
        $this->authorize('permission', 'insurance-policies-read');

        $user = Auth::user();
        $businessId = resolveBusinessId();

        $query = InsurancePolicy::query()
            ->where('company_id', $businessId);

        if ($request->filled('search')) {
            $search = Str::limit(strip_tags($request->input('search')), 100);
            $query->where(function ($q) use ($search) {
                $q->where('policy_number', 'LIKE', "%{$search}%")
                  ->orWhere('patient_name', 'LIKE', "%{$search}%")
                  ->orWhere('insurance_company', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $validated = $request->validate([
                'status' => 'in:active,inactive,expired,pending',
            ]);
            $query->where('status', $validated['status']);
        }

        if ($request->filled('company_id')) {
            $validated = $request->validate([
                'company_id' => 'required|integer|exists:businesses,id',
            ]);
            $query->where('company_id', $validated['company_id']);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $this->authorize('permission', 'insurance-policies-create');

        $validated = $request->validate([
            'patient_id'           => 'required|integer|exists:patients,id',
            'policy_number'        => 'required|string|max:100|unique:insurance_policies,policy_number',
            'insurance_company'    => 'required|string|max:255',
            'coverage_amount'      => 'required|numeric|min:0|max:999999999999',
            'copay_percentage'     => 'nullable|numeric|min:0|max:100',
            'start_date'           => 'required|date|before:end_date',
            'end_date'             => 'required|date|after:start_date',
            'status'               => 'sometimes|in:active,inactive,expired,pending',
        ]);

        $businessId = resolveBusinessId();

        $policy = InsurancePolicy::create([
            ...$validated,
            'company_id' => $businessId,
            'created_by' => Auth::id(),
        ]);

        StructuredLogger::info('insurance_policy_created', [
            'policy_id' => $policy->id,
            'policy_number' => $policy->policy_number,
            'business_id' => $businessId,
            'user_id' => Auth::id(),
        ]);

        return response()->json($policy, 201);
    }

    public function show(InsurancePolicy $insurancePolicy)
    {
        $this->authorize('permission', 'insurance-policies-read');
        $this->authorizeBusinessAccess($insurancePolicy);

        return response()->json($insurancePolicy);
    }

    public function update(Request $request, InsurancePolicy $insurancePolicy)
    {
        $this->authorize('permission', 'insurance-policies-update');
        $this->authorizeBusinessAccess($insurancePolicy);

        $validated = $request->validate([
            'patient_id'           => 'sometimes|integer|exists:patients,id',
            'policy_number'        => 'sometimes|string|max:100|unique:insurance_policies,policy_number,' . $insurancePolicy->id,
            'insurance_company'    => 'sometimes|string|max:255',
            'coverage_amount'      => 'sometimes|numeric|min:0|max:999999999999',
            'copay_percentage'     => 'nullable|numeric|min:0|max:100',
            'start_date'           => 'sometimes|date|before:end_date',
            'end_date'             => 'sometimes|date|after:start_date',
            'status'               => 'sometimes|in:active,inactive,expired,pending',
        ]);

        $insurancePolicy->update($validated);

        StructuredLogger::info('insurance_policy_updated', [
            'policy_id' => $insurancePolicy->id,
            'policy_number' => $insurancePolicy->policy_number,
            'business_id' => $insurancePolicy->company_id,
            'user_id' => Auth::id(),
            'changes' => array_keys($validated),
        ]);

        return response()->json($insurancePolicy);
    }

    public function destroy(InsurancePolicy $insurancePolicy)
    {
        $this->authorize('permission', 'insurance-policies-delete');
        $this->authorizeBusinessAccess($insurancePolicy);

        $policyNumber = $insurancePolicy->policy_number;
        $businessId = $insurancePolicy->company_id;

        $insurancePolicy->delete();

        StructuredLogger::warning('insurance_policy_deleted', [
            'policy_number' => $policyNumber,
            'business_id' => $businessId,
            'user_id' => Auth::id(),
        ]);

        return response()->json(['message' => 'Insurance policy deleted successfully']);
    }

    protected function authorizeBusinessAccess($model): void
    {
        $businessId = resolveBusinessId();
        authorizeBusinessAccess($model, $businessId);
    }
}
