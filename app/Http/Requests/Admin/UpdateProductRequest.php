<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_products') || $this->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        $productId = $this->route('product')->id;

        return [
            'product_category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,'.$productId],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug,'.$productId],
            'description' => ['nullable', 'string'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'track_inventory' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
