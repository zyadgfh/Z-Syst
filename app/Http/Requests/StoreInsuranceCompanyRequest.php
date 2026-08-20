<?php

namespace App\Http\Requests;

class StoreInsuranceCompanyRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\InsuranceCompany::class);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:insurance_companies,code,NULL,id,business_id,' . auth()->user()?->business_id,
            'contact_person' => 'nullable|string|max:255',
            'phone' => $this->phoneRules(),
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Company name is required'),
            'code.unique' => __('Company code already exists'),
        ];
    }
}
