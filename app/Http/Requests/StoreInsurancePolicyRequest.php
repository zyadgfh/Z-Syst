<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreInsurancePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'customer_id' => 'nullable|exists:parties,id',
            'policy_number' => 'nullable|string|max:100',
            'member_id' => 'nullable|string|max:100',
            'card_number' => 'nullable|string|max:100',
            'holder_name' => 'required|string|max:200',
            'holder_dob' => 'nullable|date',
            'holder_gender' => 'nullable|in:male,female,other',
            'holder_phone' => 'nullable|string|max:50',
            'holder_email' => 'nullable|email|max:200',
            'holder_address' => 'nullable|string|max:500',
            'plan_type' => 'nullable|in:individual,family,corporate,government',
            'status' => 'nullable|in:active,expired,suspended,cancelled,pending',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'annual_limit' => 'nullable|numeric|min:0',
            'coverage_percent' => 'nullable|numeric|min:0|max:100',
            'copay_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
