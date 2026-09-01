<?php

namespace App\Http\Requests;

class StoreInsuranceClaimRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_policy_id' => 'required|exists:insurance_policies,id',
            'insurance_company_id' => 'nullable|exists:insurance_companies,id',
            'service_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'covered_amount' => 'nullable|numeric|min:0',
            'patient_responsibility' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'insurance_policy_id.required' => __('Insurance policy is required'),
            'service_date.required' => __('Service date is required'),
            'total_amount.required' => __('Claim amount is required'),
        ];
    }
}
