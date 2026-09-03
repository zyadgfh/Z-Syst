<?php

namespace App\Http\Requests;

class StoreCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessId = $this->user()->business_id;

        return [
            'categoryName' => 'required|string|max:255|unique:categories,categoryName,NULL,id,business_id,'.$businessId,
            'description' => 'nullable|string|max:1000',
        ];
    }
}
