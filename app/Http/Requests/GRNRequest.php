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
        $isUpdate = $this->routeIs('grn.update');

        return [
            'purchase_order_id' => ($isUpdate ? 'nullable' : 'required') . '|exists:purchase_orders,id',
            'supplier_id' => ($isUpdate ? 'nullable' : 'required') . '|exists:parties,id',
            'warehouse_id' => ($isUpdate ? 'nullable' : 'required') . '|exists:warehouses,id',
            'location' => 'nullable|string|max:255',
            'received_date' => ($isUpdate ? 'nullable' : 'required') . '|date',
            'notes' => 'nullable|string|max:1000',
            'items' => ($isUpdate ? 'nullable' : 'required') . '|array',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity_received' => 'required_with:items|integer|min:0',
            'items.*.quantity_accepted' => 'nullable|integer|min:0',
            'items.*.quantity_rejected' => 'nullable|integer|min:0',
            'items.*.unit_cost' => 'required_with:items|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'purchase_order_id.required' => 'Purchase order is required.',
            'purchase_order_id.exists' => 'The selected purchase order is invalid.',
            'supplier_id.required' => 'Supplier is required.',
            'supplier_id.exists' => 'The selected supplier is invalid.',
            'warehouse_id.required' => 'Warehouse is required.',
            'warehouse_id.exists' => 'The selected warehouse is invalid.',
            'received_date.required' => 'Received date is required.',
            'items.required' => 'Items are required.',
            'items.min' => 'At least one item is required.',
            'items.*.product_id.required' => 'Product is required for each item.',
            'items.*.product_id.exists' => 'The selected product is invalid.',
            'items.*.quantity_received.required' => 'Quantity received is required for each item.',
            'items.*.quantity_received.integer' => 'Quantity received must be an integer.',
            'items.*.quantity_received.min' => 'Quantity received must be at least 0.',
            'items.*.unit_cost.required' => 'Unit cost is required for each item.',
            'items.*.unit_cost.numeric' => 'Unit cost must be a number.',
            'items.*.unit_cost.min' => 'Unit cost must be at least 0.',
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
