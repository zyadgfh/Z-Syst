<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBatchLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'batch_number' => 'required|string|max:100',
            'lot_number' => 'nullable|string|max:100',
            'quantity' => 'required|numeric|min:0',
            'manufacturing_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:manufacturing_date',
            'supplier_id' => 'nullable|exists:parties,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'storage_location' => 'nullable|string|max:255',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:available,sold,expired,recalled,damaged',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => 'Invalid product selected.',
            'expiry_date.after_or_equal' => 'Expiry date must be after or equal to manufacturing date.',
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
