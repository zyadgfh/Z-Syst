<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStockAuditDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_audit_id' => 'required|exists:stock_audits,id',
            'product_id' => 'required|exists:products,id',
            'stock_id' => 'nullable|exists:stocks,id',
            'batch_no' => 'nullable|string|max:100',
            'expire_date' => 'nullable|date',
            'physical_quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'stock_audit_id.required' => 'Stock audit ID is required.',
            'stock_audit_id.exists' => 'Selected stock audit does not exist.',
            'product_id.required' => 'Product ID is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'stock_id.exists' => 'Selected stock record does not exist.',
            'batch_no.max' => 'Batch number must not exceed 100 characters.',
            'expire_date.date' => 'Expiry date must be a valid date.',
            'physical_quantity.required' => 'Physical quantity is required.',
            'physical_quantity.integer' => 'Physical quantity must be an integer.',
            'physical_quantity.min' => 'Physical quantity cannot be negative.',
            'notes.max' => 'Notes must not exceed 500 characters.',
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
