<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreEInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Invoice::class);
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'nullable|exists:branches,id',
            'sale_id' => 'nullable|exists:sales,id',
            'party_id' => 'required|exists:parties,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'invoice_number' => 'nullable|string|max:50|unique:invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'balance' => 'nullable|numeric',
            'status' => 'nullable|in:draft,sent,paid,partial,overdue,cancelled',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'is_proforma' => 'sometimes|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:1000',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_id' => 'nullable|exists:taxes,id',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.unit_of_measure' => 'nullable|string|max:20',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}