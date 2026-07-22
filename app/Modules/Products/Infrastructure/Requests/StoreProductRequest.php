<?php

declare(strict_types=1);

namespace App\Modules\Products\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for creating a product.
 *
 * Validation rules for all required and optional fields.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_products') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            // Basic info
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'product_code')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'string', 'exists:categories,id'],
            'manufacturer_id' => ['nullable', 'string', 'exists:manufacturers,id'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],

            // Initial stock (optional)
            'batch_number' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'quantity_available' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المنتج مطلوب',
            'barcode.unique' => 'الباركود موجود مسبقاً',
            'sku.unique' => 'كود المنتج موجود مسبقاً',
            'sale_price.required' => 'سعر البيع مطلوب',
            'sale_price.min' => 'سعر البيع يجب أن يكون 0 أو أكثر',
            'category_id.exists' => 'الفئة غير موجودة',
            'manufacturer_id.exists' => 'المصنع غير موجود',
            'expiry_date.after' => 'تاريخ الصلاحية يجب أن يكون في المستقبل',
        ];
    }
}

