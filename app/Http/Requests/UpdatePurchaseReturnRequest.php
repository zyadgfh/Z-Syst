<?php

namespace App\Http\Requests;

class UpdatePurchaseReturnRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_id' => 'required|exists:purchases,id',
            'return_date' => 'required|date',
            'purchase_detail_id' => 'required|array|min:1',
            'purchase_detail_id.*' => 'required|exists:purchase_details,id',
            'return_amount' => 'required|array|min:1',
            'return_amount.*' => 'required|numeric|min:0',
            'return_qty' => 'required|array|min:1',
            'return_qty.*' => 'required|integer|min:1',
            'dueAmount' => 'nullable|numeric|min:0',
            'paidAmount' => 'nullable|numeric|min:0',
            'totalAmount' => 'nullable|numeric|min:0',
            'discountAmount' => 'nullable|numeric|min:0',
        ];
    }
}
