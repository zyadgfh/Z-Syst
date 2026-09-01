<?php

namespace App\Http\Requests;

class StoreDuePaymentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'party_id' => 'required|exists:parties,id',
            'paymentType' => 'required|string|max:50',
            'paymentDate' => 'required|string|max:30',
            'payDueAmount' => 'required|numeric|min:0.01|max:999999999.99',
            'invoiceNumber' => 'nullable|string|max:50',
        ];
    }
}
