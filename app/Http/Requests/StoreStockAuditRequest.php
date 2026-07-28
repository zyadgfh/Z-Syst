<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStockAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'audit_type' => 'required|in:periodic,manual,spot_check,financial',
            'audit_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'business_id.required' => 'Business ID is required.',
            'business_id.exists' => 'Selected business does not exist.',
            'audit_type.required' => 'Audit type is required.',
            'audit_type.in' => 'Invalid audit type. Must be periodic, manual, spot_check, or financial.',
            'audit_date.date' => 'Audit date must be a valid date.',
            'notes.max' => 'Notes must not exceed 1000 characters.',
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