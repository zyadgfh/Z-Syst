<?php

namespace App\Http\Requests;

class UpdateExpenseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => $this->monetaryRules(),
            'expense_category_id' => 'required|exists:expense_categories,id',
        ];
    }
}
