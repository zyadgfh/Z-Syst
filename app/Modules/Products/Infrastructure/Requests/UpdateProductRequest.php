<?php

declare(strict_types=1);

namespace App\Modules\Products\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for updating a product.
 *
 * All fields are optional — only provided fields will be updated.
 */
class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_products') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $productId = $this->route('product')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($productId),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'product_code')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($productId),
            ],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'اسم المنتج يجب أن يكون نصاً',
            'barcode.unique' => 'الباركود موجود مسبقاً',
            'sku.unique' => 'كود المنتج موجود مسبقاً',
            'sale_price.min' => 'سعر البيع يجب أن يكون 0 أو أكثر',
            'category_id.exists' => 'الفئة غير موجودة',
            'manufacturer_id.exists' => 'المصنع غير موجود',
        ];
    }
}

