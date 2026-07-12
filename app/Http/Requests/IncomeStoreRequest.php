<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncomeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'income_category_id' => 'required|exists:income_categories,id',
            'amount' => 'required|numeric|min:0',
            'incomeFor' => 'nullable|string',
            'paymentType' => 'nullable|string',
            'referenceNo' => 'nullable|string',
            'note' => 'nullable|string',
            'incomeDate' => 'required|date',
        ];
    }
}