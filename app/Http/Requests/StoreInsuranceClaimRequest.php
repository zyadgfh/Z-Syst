<?php

namespace App\Http\Requests;

class StoreInsuranceClaimRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\InsuranceClaim::class);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'company_id' => 'required|exists:insurance_companies,id',
            'policy_id' => 'required|exists:insurance_policies,id',
            'patient_name' => 'required|string|max:255',
            'patient_phone' => 'nullable|string|max:20',
            'claim_amount' => 'required|numeric|min:0',
            'claim_date' => 'required|date',
            'description' => 'nullable|string|max:2000',
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => __('Insurance company is required'),
            'policy_id.required' => __('Insurance policy is required'),
            'patient_name.required' => __('Patient name is required'),
            'claim_amount.required' => __('Claim amount is required'),
        ];
    }
}
