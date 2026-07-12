<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'expanseFor' => 'nullable|string',
            'paymentType' => 'nullable|string',
            'referenceNo' => 'nullable|string',
            'note' => 'nullable|string',
            'expenseDate' => 'required|date',
        ];
    }
}