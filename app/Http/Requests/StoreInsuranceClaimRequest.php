<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreInsuranceClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_policy_id' => 'required|exists:insurance_policies,id',
            'sale_id' => 'nullable|exists:sales,id',
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'service_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'line_items' => 'nullable|array',
            'line_items.*.description' => 'required_with:line_items|string|max:255',
            'line_items.*.amount' => 'required_with:line_items|numeric|min:0',
            'line_items.*.covered' => 'nullable|numeric|min:0',
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
