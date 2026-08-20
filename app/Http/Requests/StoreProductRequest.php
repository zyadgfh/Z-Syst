<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Product::class);
    }

    public function rules(): array
    {
        $business_id = $this->user()->business_id;

        return [
            'productName' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'type_id' => 'nullable|integer|exists:medicine_types,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'box_size_id' => 'nullable|integer|exists:box_sizes,id',
            'productCode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products')->where(function ($query) use ($business_id) {
                    return $query->where('business_id', $business_id);
                }),
            ],
            'batch_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('stocks')->where(function ($query) use ($business_id) {
                    return $query->where('business_id', $business_id);
                }),
            ],
            'purchase_without_tax' => 'nullable|numeric|min:0',
            'purchase_with_tax' => 'nullable|numeric|min:0',
            'profit_percent' => 'nullable|numeric|min:0|max:100',
            'sales_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'alert_qty' => 'nullable|integer|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'tax_type' => 'nullable|in:inclusive,exclusive',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:2048',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => __('Validation failed.'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}