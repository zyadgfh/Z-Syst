<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoodsReceivedNoteRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'nullable|exists:parties,id',
            'branch_id' => 'nullable|exists:branches,id',
            'received_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.received_quantity' => 'required|integer|min:1',
            'items.*.condition' => 'required|in:good,damaged,expired,returned',
            'items.*.batch_number' => 'nullable|string|max:255',
            'items.*.expiry_date' => 'nullable|date|after:today',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.rejection_reason' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'purchase_order_id.exists' => 'The selected purchase order does not exist.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'branch_id.exists' => 'The selected branch does not exist.',
            'received_date.required' => 'The received date is required.',
            'received_date.date' => 'The received date must be a valid date.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.product_id.required' => 'The product is required for each item.',
            'items.*.product_id.exists' => 'The selected product does not exist.',
            'items.*.received_quantity.required' => 'The received quantity is required for each item.',
            'items.*.received_quantity.integer' => 'The received quantity must be a whole number.',
            'items.*.received_quantity.min' => 'The received quantity must be at least 1.',
            'items.*.condition.required' => 'The item condition is required.',
            'items.*.condition.in' => 'The condition must be one of: good, damaged, expired, returned.',
            'items.*.expiry_date.date' => 'The expiry date must be a valid date.',
            'items.*.expiry_date.after' => 'The expiry date must be in the future.',
            'items.*.unit_cost.required' => 'The unit cost is required for each item.',
            'items.*.unit_cost.numeric' => 'The unit cost must be a number.',
            'items.*.unit_cost.min' => 'The unit cost cannot be negative.',
            'items.*.rejection_reason.max' => 'The rejection reason may not exceed 500 characters.',
        ];
    }
}
