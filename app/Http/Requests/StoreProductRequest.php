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
            // Identity
            'productName' => 'required|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'commercial_name' => 'nullable|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'sku' => [
                'nullable', 'string', 'max:100',
                Rule::unique('products')->where(fn($q) => $q->where('business_id', $business_id)),
            ],
            'internal_code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('products')->where(fn($q) => $q->where('business_id', $business_id)),
            ],
            'productCode' => [
                'nullable', 'string', 'max:100',
                Rule::unique('products')->where(fn($q) => $q->where('business_id', $business_id)),
            ],
            'barcode' => [
                'nullable', 'string', 'max:100',
                Rule::unique('products')->where(fn($q) => $q->where('business_id', $business_id)),
            ],
            'barcode_type' => 'nullable|string|in:CODE128,EAN13,UPC,QR',
            'gtin' => 'nullable|string|max:30',

            // Classification
            'category_id' => 'required|integer|exists:categories,id',
            'subcategory_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:manufacturers,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'type_id' => 'nullable|integer|exists:medicine_types,id',
            'box_size_id' => 'nullable|integer|exists:box_sizes,id',
            'product_type' => 'nullable|string|in:product,service,combo',
            'dosage_form' => 'nullable|string|max:100',
            'route_of_administration' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'unit_type' => 'nullable|string|max:50',

            // Units
            'unit_id' => 'nullable|integer|exists:units,id',
            'purchase_unit_id' => 'nullable|integer|exists:units,id',
            'sales_unit_id' => 'nullable|integer|exists:units,id',
            'conversion_factor' => 'nullable|numeric|min:0.0001',
            'allow_fractional_quantity' => 'nullable|boolean',

            // Pricing
            'purchase_without_tax' => 'nullable|numeric|min:0',
            'purchase_with_tax' => 'nullable|numeric|min:0',
            'profit_percent' => 'nullable|numeric|min:0|max:100',
            'sales_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'minimum_selling_price' => 'nullable|numeric|min:0',
            'special_price' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|integer|exists:taxes,id',
            'tax_type' => 'nullable|in:inclusive,exclusive',
            'tax_included' => 'nullable|boolean',

            // Inventory
            'alert_qty' => 'nullable|integer|min:0',
            'track_inventory' => 'nullable|boolean',
            'minimum_stock' => 'nullable|integer|min:0',
            'maximum_stock' => 'nullable|integer|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:0',
            'safety_stock' => 'nullable|integer|min:0',
            'allow_negative_stock' => 'nullable|boolean',

            // Pharmacy
            'active_ingredient' => 'nullable|string|max:255',
            'concentration' => 'nullable|string|max:100',
            'dosage' => 'nullable|string|max:100',
            'package_size' => 'nullable|integer|min:1',
            'package_unit' => 'nullable|string|max:50',
            'prescription_required' => 'nullable|boolean',
            'controlled_item' => 'nullable|boolean',
            'refrigerated' => 'nullable|boolean',
            'temperature_requirements' => 'nullable|string|max:100',
            'storage_instructions' => 'nullable|string|max:500',

            // Expiration
            'track_expiration' => 'nullable|boolean',
            'minimum_remaining_shelf_life' => 'nullable|integer|min:0',
            'expiration_warning_days' => 'nullable|integer|min:0',

            // Supplier
            'preferred_supplier_id' => 'nullable|integer|exists:parties,id',
            'supplier_ids' => 'nullable|array',
            'supplier_ids.*' => 'integer|exists:parties,id',

            // Status
            'active' => 'nullable|boolean',
            'discontinued' => 'nullable|boolean',

            // Stock
            'batch_no' => 'nullable|string|max:100',
            'expire_date' => 'nullable|date',
            'qty' => 'nullable|integer|min:0',
            'branch_id' => 'nullable|integer|exists:branches,id',

            // Images
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:2048',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => __('Validation failed.'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
