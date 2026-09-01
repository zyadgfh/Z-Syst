<?php

namespace App\Http\Requests;

class UpdateIncomeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => $this->monetaryRules(),
            'income_category_id' => 'required|integer|exists:income_categories,id',
        ];
    }
}
