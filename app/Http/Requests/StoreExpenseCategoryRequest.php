<?php

namespace App\Http\Requests;

class StoreExpenseCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categoryName' => 'required|string|max:100|unique:expense_categories,categoryName,NULL,id,business_id,' . auth()->user()->business_id,
            'status' => 'nullable|string|in:true,false,1,0',
        ];
    }
}
