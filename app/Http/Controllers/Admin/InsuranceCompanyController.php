<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\InsuranceCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InsuranceCompanyController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $companies = InsuranceCompany::forCompany($request->user()->company_id)
            ->withCount(['plans', 'claims'])
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return $this->success($companies);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'discount_percentage' => 'numeric|min:0|max:100',
            'contract_terms' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $data = $validator->validated();
        $data['code'] = strtoupper(Str::slug($data['name'])) . '-' . Str::random(4);
        $data['company_id'] = $request->user()->company_id;
        $data['created_by'] = $request->user()->id;

        $company = InsuranceCompany::create($data);

        return $this->created($company, 'Insurance company created successfully');
    }

    public function show(Request $request, InsuranceCompany $insuranceCompany): JsonResponse
    {
        if ($insuranceCompany->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        return $this->success($insuranceCompany->load(['plans', 'claims']));
    }

    public function update(Request $request, InsuranceCompany $insuranceCompany): JsonResponse
    {
        if ($insuranceCompany->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'discount_percentage' => 'numeric|min:0|max:100',
            'contract_terms' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $insuranceCompany->update(array_merge($validator->validated(), ['updated_by' => $request->user()->id]));
        return $this->success($insuranceCompany, 'Insurance company updated successfully');
    }

    public function destroy(Request $request, InsuranceCompany $insuranceCompany): JsonResponse
    {
        if ($insuranceCompany->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        $insuranceCompany->delete();
        return $this->success(null, 'Insurance company deleted successfully');
    }
}