<?php

namespace App\Http\Requests;

class StoreSaleReturnRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\SaleReturn::class);
    }

    public function rules(): array
    {
        return [
            'sale_id' => 'required|exists:sales,id',
            'return_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.sale_detail_id' => 'required|exists:sale_details,id',
            'items.*.return_qty' => 'required|integer|min:1',
            'items.*.return_amount' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'sale_id.required' => __('Sale ID is required.'),
            'sale_id.exists' => __('Selected sale does not exist.'),
            'items.required' => __('At least one return item is required.'),
            'items.min' => __('At least one return item is required.'),
            'items.*.return_qty.min' => __('Return quantity must be at least 1.'),
        ];
    }
}
