<?php

namespace App\Http\Requests;

class UpdateSaleReturnRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sale_id' => 'required|exists:sales,id',
            'return_date' => 'required|date',
            'sale_detail_id' => 'required|array|min:1',
            'sale_detail_id.*' => 'required|exists:sale_details,id',
            'return_amount' => 'required|array|min:1',
            'return_amount.*' => 'required|numeric|min:0',
            'return_qty' => 'required|array|min:1',
            'return_qty.*' => 'required|integer|min:1',
            'dueAmount' => 'nullable|numeric|min:0',
            'paidAmount' => 'nullable|numeric|min:0',
            'totalAmount' => 'nullable|numeric|min:0',
            'discountAmount' => 'nullable|numeric|min:0',
            'lossProfit' => 'nullable|array',
        ];
    }
}
