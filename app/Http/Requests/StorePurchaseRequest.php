<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Purchase::class);
    }

    public function rules(): array
    {
        return [
            'products' => 'required|array|min:1',
            'purchaseDate' => 'required|date',
            'party_id' => 'required|exists:parties,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'discountAmount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'totalAmount' => 'nullable|numeric|min:0',
            'dueAmount' => 'nullable|numeric|min:0',
            'paidAmount' => 'nullable|numeric|min:0',
            'isPaid' => 'nullable|boolean',
            'paymentType' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:1000',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.purchase_without_tax' => 'required|numeric|min:0',
            'products.*.purchase_with_tax' => 'required|numeric|min:0',
            'products.*.profit_percent' => 'required|numeric|min:0|max:100',
            'products.*.sales_price' => 'required|numeric|min:0',
            'products.*.wholesale_price' => 'required|numeric|min:0',
            'products.*.batch_no' => 'nullable|string|max:100',
            'products.*.expire_date' => 'nullable|date',
            'products.*.quantities' => 'required|integer|min:1',
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