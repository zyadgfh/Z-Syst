<?php

namespace App\Http\Requests;

class StoreIncomeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->monetaryRules(), [
            'amount' => $this->monetaryRules(),
            'income_category_id' => 'required|integer|exists:income_categories,id',
        ]);
    }

    public function attributes(): array
    {
        return [
            'amount' => 'amount',
            'income_category_id' => 'income category',
        ];
    }
}
