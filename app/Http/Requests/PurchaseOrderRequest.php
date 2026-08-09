<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
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
            'supplier_id' => 'nullable|exists:parties,id',
            'priority' => 'required|in:low,normal,high,urgent',
            'expected_delivery_date' => 'nullable|date|after:today',
            'terms' => 'nullable|string|max:5000',
            'internal_notes' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'priority.in' => 'The priority must be one of: low, normal, high, urgent.',
            'expected_delivery_date.after' => 'The delivery date must be after today.',
            'items.required' => 'At least one item is required.',
            'items.*.product_id.exists' => 'The selected product does not exist.',
            'items.*.quantity.min' => 'The quantity must be at least 1.',
            'items.*.unit_price.min' => 'The unit price must be at least 0.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => $this->user()->business_id ?? null,
            'branch_id' => $this->user()->branch_id ?? null,
        ]);
    }
}
