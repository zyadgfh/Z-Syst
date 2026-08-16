<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Services\InsuranceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InsuranceCompanyController extends Controller
{
    protected $insuranceService;

    public function __construct(InsuranceService $insuranceService)
    {
        $this->insuranceService = $insuranceService;
        $this->middleware('permission:insurance-companies-*');
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $companies = InsuranceCompany::query()
            ->with(['business:id,companyName'])
            ->forBusiness()
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $companies,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:insurance_companies,code',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'business_id' => [
                'required',
                'exists:businesses,id',
                function ($attribute, $value, $fail) {
                    if ($value !== Auth::user()->business_id) {
                        $fail('You are not authorized to create insurance companies for this business.');
                    }
                },
            ],
        ]);

        $company = $this->insuranceService->store($validated);

        return response()->json([
            'success' => true,
            'message' => 'Insurance company created successfully',
            'data' => $company,
        ], 201);
    }

    public function show(InsuranceCompany $insuranceCompany)
    {
        $this->authorizeBusinessAccess($insuranceCompany);

        $insuranceCompany->load(['business:id,companyName']);

        return response()->json([
            'success' => true,
            'data' => $insuranceCompany,
        ]);
    }

    public function update(Request $request, InsuranceCompany $insuranceCompany)
    {
        $this->authorizeBusinessAccess($insuranceCompany);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('insurance_companies', 'code')->ignore($insuranceCompany->id),
            ],
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        $company = $this->insuranceService->update($insuranceCompany, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Insurance company updated successfully',
            'data' => $company,
        ]);
    }

    public function destroy(InsuranceCompany $insuranceCompany)
    {
        $this->authorizeBusinessAccess($insuranceCompany);

        $insuranceCompany->delete();

        return response()->json([
            'success' => true,
            'message' => 'Insurance company deleted successfully',
        ]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $searchQuery = $validated['query'];

        $companies = InsuranceCompany::query()
            ->with(['business:id,companyName'])
            ->forBusiness()
            ->where(function ($q) use ($searchQuery) {
                $q->where('name', 'like', '%' . $searchQuery . '%')
                  ->orWhere('code', 'like', '%' . $searchQuery . '%')
                  ->orWhere('contact_person', 'like', '%' . $searchQuery . '%');
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $companies,
        ]);
    }

    /**
     * Authorize that the insurance company belongs to the authenticated user's business.
     */
    protected function authorizeBusinessAccess(InsuranceCompany $insuranceCompany): void
    {
        if ($insuranceCompany->business_id !== Auth::user()->business_id) {
            abort(403, 'Unauthorized access to this insurance company.');
        }
    }
}
