<?php

namespace App\Http\Requests;

class UpdateExpenseCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'categoryName' => 'required|string|max:100|unique:expense_categories,categoryName,' . $categoryId . ',id,business_id,' . auth()->user()->business_id,
            'status' => 'nullable|string|in:true,false,1,0',
        ];
    }
}
