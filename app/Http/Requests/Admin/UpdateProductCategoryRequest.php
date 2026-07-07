<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_products') || $this->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        $categoryId = $this->route('product_category')->id;

        return [
            'parent_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:product_categories,slug,'.$categoryId],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
