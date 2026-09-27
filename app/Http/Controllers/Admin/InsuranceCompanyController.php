<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Services\InsuranceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsuranceCompanyController extends Controller
{
    protected InsuranceService $insuranceService;

    public function __construct(InsuranceService $insuranceService)
    {
        $this->insuranceService = $insuranceService;
        $this->middleware('permission:insurance-companies-create')->only('create', 'store');
        $this->middleware('permission:insurance-companies-read')->only('index', 'show');
        $this->middleware('permission:insurance-companies-update')->only('edit', 'update');
        $this->middleware('permission:insurance-companies-delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $companies = InsuranceCompany::with(['business:id,companyName'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('code', 'like', '%' . $request->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('admin.insurance.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('admin.insurance.companies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'nullable|integer|exists:businesses,id',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,suspended',
            'integration_type' => 'required|in:manual,api,hybrid',
            'api_endpoint' => 'nullable|url',
            'api_credentials' => 'nullable|array',
            'default_coverage_percent' => 'required|numeric|min:0|max:100',
            'default_copay_percent' => 'required|numeric|min:0|max:100',
            'settlement_days' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            $company = $this->insuranceService->createCompany([
                'business_id' => auth()->user()->role === 'superadmin' ? $request->business_id : auth()->user()->business_id,
                'name' => $request->name,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'country' => $request->country,
                'tax_id' => $request->tax_id,
                'status' => $request->status,
                'integration_type' => $request->integration_type,
                'api_endpoint' => $request->api_endpoint,
                'api_credentials' => $request->api_credentials,
                'default_coverage_percent' => $request->default_coverage_percent,
                'default_copay_percent' => $request->default_copay_percent,
                'settlement_days' => $request->settlement_days,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => __('Insurance company created successfully'),
                'redirect' => route('admin.insurance.companies.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error creating insurance company: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function show(InsuranceCompany $company)
    {
        $company->load(['policies', 'claims']);
        return view('admin.insurance.companies.show', compact('company'));
    }

    public function edit(InsuranceCompany $company)
    {
        return view('admin.insurance.companies.edit', compact('company'));
    }

    public function update(Request $request, InsuranceCompany $company)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,suspended',
            'integration_type' => 'required|in:manual,api,hybrid',
            'api_endpoint' => 'nullable|url',
            'api_credentials' => 'nullable|array',
            'default_coverage_percent' => 'required|numeric|min:0|max:100',
            'default_copay_percent' => 'required|numeric|min:0|max:100',
            'settlement_days' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            $company = $this->insuranceService->updateCompany($company, [
                'name' => $request->name,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'country' => $request->country,
                'tax_id' => $request->tax_id,
                'status' => $request->status,
                'integration_type' => $request->integration_type,
                'api_endpoint' => $request->api_endpoint,
                'api_credentials' => $request->api_credentials,
                'default_coverage_percent' => $request->default_coverage_percent,
                'settlement_days' => $request->settlement_days,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => __('Insurance company updated successfully'),
                'redirect' => route('admin.insurance.companies.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error updating insurance company: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(InsuranceCompany $company)
    {
        try {
            $company->delete();

            return response()->json([
                'message' => __('Insurance company deleted successfully'),
                'redirect' => route('admin.insurance.companies.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error deleting insurance company: ') . $e->getMessage(),
            ], 500);
        }
    }
}
