<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sale = $this->route('sale');
        return $this->user()->can('update', $sale);
    }

    public function rules(): array
    {
        return [
            'products' => 'required|array|min:1',
            'saleDate' => 'required|date',
            'party_id' => 'nullable|exists:parties,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'discountAmount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'totalAmount' => 'nullable|numeric|min:0',
            'dueAmount' => 'nullable|numeric|min:0',
            'paidAmount' => 'nullable|numeric|min:0',
            'isPaid' => 'nullable|boolean',
            'paymentType' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'customer_phone' => 'nullable|string|max:20',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.lossProfit' => 'required|numeric',
            'products.*.batch_no' => 'nullable|string|max:100',
            'products.*.quantities' => 'required|integer|min:1',
            'products.*.expire_date' => 'nullable|date',
            'products.*.purchase_price' => 'nullable|numeric|min:0',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => __('Validation failed.'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}