<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreInsuranceCoverageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'product_id' => 'nullable|exists:products,id|required_without:category_id',
            'category_id' => 'nullable|exists:categories,id|required_without:product_id',
            'coverage_code' => 'nullable|string|max:100',
            'scope' => 'nullable|in:product,category,all',
            'coverage_percent' => 'required|numeric|min:0|max:100',
            'copay_percent' => 'nullable|numeric|min:0|max:100',
            'max_amount_per_claim' => 'nullable|numeric|min:0',
            'max_amount_per_year' => 'nullable|numeric|min:0',
            'requires_preauthorization' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'notes' => 'nullable|string|max:1000',
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
