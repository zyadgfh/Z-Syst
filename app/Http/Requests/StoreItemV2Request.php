<?php

namespace App\Http\Requests;

class StoreItemV2Request extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productName' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'manufacturer_id' => 'nullable|exists:manufacturers,id',
            'barcode' => 'nullable|string|max:50',
            'sku' => 'nullable|string|max:50',
            'purchase_without_tax' => 'required|numeric|min:0',
            'purchase_with_tax' => 'required|numeric|min:0',
            'sales_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'alert_qty' => 'nullable|integer|min:0',
            'tax_type' => 'nullable|in:exclusive,inclusive',
            'product_type' => 'nullable|string|max:50',
            'dosage_form' => 'nullable|string|max:50',
            'strength' => 'nullable|string|max:50',
            'scientific_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'batch_no' => 'nullable|string|max:50',
            'qty' => 'nullable|integer|min:0',
        ];
    }
}
