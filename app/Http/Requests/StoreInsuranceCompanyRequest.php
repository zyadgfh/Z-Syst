<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreInsuranceCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'code' => 'required|string|max:50|unique:insurance_companies,code',
            'contact_person' => 'nullable|string|max:200',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:200',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive,suspended',
            'integration_type' => 'nullable|in:manual,api,hybrid',
            'api_endpoint' => 'nullable|url|max:500',
            'api_credentials' => 'nullable|array',
            'default_coverage_percent' => 'nullable|numeric|min:0|max:100',
            'default_copay_percent' => 'nullable|numeric|min:0|max:100',
            'settlement_days' => 'nullable|integer|min:0|max:365',
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
