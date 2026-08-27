<?php

namespace App\Http\Requests;

class UpdateIncomeCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'categoryName' => 'required|string|max:100|unique:income_categories,categoryName,' . $categoryId . ',id,business_id,' . auth()->user()->business_id,
            'status' => 'nullable|string|in:true,false,1,0',
        ];
    }
}
