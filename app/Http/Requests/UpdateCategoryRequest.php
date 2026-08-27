<?php

namespace App\Http\Requests;

class UpdateCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessId = $this->user()->business_id;
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'categoryName' => [
                'required', 'string', 'max:255',
                'unique:categories,categoryName,'.$categoryId.',id,business_id,'.$businessId,
            ],
            'description' => 'nullable|string|max:1000',
        ];
    }
}
