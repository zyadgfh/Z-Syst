<?php

namespace App\Modules\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'party_id' => 'nullable|exists:parties,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantities' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.purchase_price' => 'nullable|numeric|min:0',
            'products.*.stock_id' => 'nullable|exists:stocks,id',
            'totalAmount' => 'required|numeric|min:0',
            'paidAmount' => 'required|numeric|min:0',
            'dueAmount' => 'nullable|numeric|min:0',
            'isPaid' => 'nullable|boolean',
            'paymentType' => 'required|string|in:Cash,Card,Bank,Credit',
            'tax_id' => 'nullable|exists:taxes,id',
            'discountAmount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'saleDate' => 'nullable|date',
            'meta' => 'nullable|array',
        ];
    }
}
