<?php

namespace App\Http\Requests;

class UpdateItemV2Request extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productName' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
            'unit_id' => 'sometimes|required|exists:units,id',
            'manufacturer_id' => 'nullable|exists:manufacturers,id',
            'barcode' => 'nullable|string|max:50',
            'sku' => 'nullable|string|max:50',
            'purchase_without_tax' => 'sometimes|required|numeric|min:0',
            'purchase_with_tax' => 'sometimes|required|numeric|min:0',
            'sales_price' => 'sometimes|required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'alert_qty' => 'nullable|integer|min:0',
            'tax_type' => 'nullable|in:exclusive,inclusive',
            'is_active' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ];
    }
}
