<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GRNRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'nullable|exists:parties,id',
            'location' => 'nullable|string|max:255',
            'received_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.ordered_quantity' => 'required|integer|min:0',
            'items.*.received_quantity' => 'required|integer|min:0',
            'items.*.accepted_quantity' => 'nullable|integer|min:0',
            'items.*.rejected_quantity' => 'nullable|integer|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'purchase_order_id.exists' => 'The selected purchase order is invalid.',
            'supplier_id.exists' => 'The selected supplier is invalid.',
            'items.*.product_id.required' => 'Product is required for each item.',
            'items.*.product_id.exists' => 'The selected product is invalid.',
            'items.*.ordered_quantity.required' => 'Ordered quantity is required for each item.',
            'items.*.ordered_quantity.integer' => 'Ordered quantity must be an integer.',
            'items.*.ordered_quantity.min' => 'Ordered quantity must be at least 0.',
            'items.*.received_quantity.required' => 'Received quantity is required for each item.',
            'items.*.received_quantity.integer' => 'Received quantity must be an integer.',
            'items.*.received_quantity.min' => 'Received quantity must be at least 0.',
            'items.*.purchase_price.required' => 'Purchase price is required for each item.',
            'items.*.purchase_price.numeric' => 'Purchase price must be a number.',
            'items.*.purchase_price.min' => 'Purchase price must be at least 0.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
