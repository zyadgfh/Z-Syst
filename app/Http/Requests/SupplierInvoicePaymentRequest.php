<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierInvoicePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'payment_date' => 'nullable|date',
            'payment_method' => 'required|in:cash,bank_transfer,check,credit_card,debit_card,online',
            'payment_reference' => 'nullable|string|max:100',
            'bank_reference' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:2000',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_method.in' => 'The payment method must be one of: cash, bank_transfer, check, credit_card, debit_card, online.',
            'amount.min' => 'The amount must be at least 0.01.',
            'file.mimes' => 'The file must be a PDF, JPG, JPEG, or PNG.',
            'file.max' => 'The file must not exceed 10MB.',
        ];
    }
}
