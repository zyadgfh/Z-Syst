<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InsuranceCompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companies = InsuranceCompany::forCompany($request->user()->company_id)
            ->with(['createdBy:id,name'])
            ->when($request->search, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('name', 'like', "%{$v}%")
                        ->orWhere('code', 'like', "%{$v}%")
                        ->orWhere('phone', 'like', "%{$v}%");
                });
            })
            ->when($request->is_active, fn($q, $v) => $q->where('is_active', $v === 'true' || $v === '1'))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($companies);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:insurance_companies,code',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'contract_terms' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $company = InsuranceCompany::create($validator->validated() + [
            'company_id' => $request->user()->company_id,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($company->load(['createdBy:id,name']), 201);
    }

    public function show(Request $request, InsuranceCompany $insuranceCompany): JsonResponse
    {
        if ($insuranceCompany->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($insuranceCompany->load(['createdBy:id,name', 'plans', 'claims' => function ($q) {
            $q->latest()->limit(10);
        }]));
    }

    public function update(Request $request, InsuranceCompany $insuranceCompany): JsonResponse
    {
        if ($insuranceCompany->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:insurance_companies,code,' . $insuranceCompany->id,
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'sometimes|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'contract_terms' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $insuranceCompany->update($validator->validated() + [
            'updated_by' => $request->user()->id,
        ]);

        return response()->json($insuranceCompany->fresh());
    }

    public function destroy(Request $request, InsuranceCompany $insuranceCompany): JsonResponse
    {
        if ($insuranceCompany->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($insuranceCompany->claims()->exists()) {
            return response()->json(['message' => 'Cannot delete insurance company with existing claims'], 422);
        }

        $insuranceCompany->delete();

        return response()->json(['message' => 'Insurance company deleted successfully']);
    }
}