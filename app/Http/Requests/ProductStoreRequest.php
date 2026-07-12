<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id ?? app('tenant.company_id');

        return [
            'productName' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'type_id' => 'nullable|integer|exists:medicine_types,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'box_size_id' => 'nullable|integer|exists:box_sizes,id',
            'productCode' => [
                'nullable',
                Rule::unique('products')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'batch_no' => [
                'nullable',
                Rule::unique('stocks')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'purchase_without_tax' => 'nullable|numeric',
            'purchase_with_tax' => 'nullable|numeric',
            'profit_percent' => 'nullable|numeric',
            'sales_price' => 'nullable|numeric',
            'wholesale_price' => 'nullable|numeric',
            'alert_qty' => 'nullable|integer',
            'tax_id' => 'nullable|exists:taxes,id',
            'tax_type' => 'nullable|string',
            'images' => 'nullable|array',
            'meta' => 'nullable|array',
        ];
    }
}