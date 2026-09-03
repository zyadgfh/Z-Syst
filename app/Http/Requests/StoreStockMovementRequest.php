<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->business_id !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'stock_id' => ['nullable', 'integer', 'exists:stocks,id'],
            'movement_type' => ['required', 'string', 'in:in,out,adjustment,transfer,recall'],
            'quantity' => $this->stockQuantityRules(),
            'batch_no' => ['nullable', 'string', 'max:100'],
            'expire_date' => ['nullable', 'date'],
            'reference_type' => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => __('Product is required.'),
            'product_id.exists' => __('Selected product does not exist.'),
            'stock_id.exists' => __('Selected stock does not exist.'),
            'movement_type.required' => __('Movement type is required.'),
            'movement_type.in' => __('Invalid movement type.'),
            'quantity.required' => __('Quantity is required.'),
            'quantity.integer' => __('Quantity must be an integer.'),
            'quantity.min' => __('Quantity cannot be negative.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => __('Product'),
            'stock_id' => __('Stock'),
            'movement_type' => __('Movement Type'),
            'quantity' => __('Quantity'),
            'batch_no' => __('Batch Number'),
            'expire_date' => __('Expiry Date'),
            'reference_type' => __('Reference Type'),
            'reference_id' => __('Reference ID'),
            'notes' => __('Notes'),
        ];
    }
}
