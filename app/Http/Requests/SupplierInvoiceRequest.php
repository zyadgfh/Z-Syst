<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierInvoiceRequest extends FormRequest
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
            'supplier_id' => 'nullable|exists:parties,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'invoice_date' => 'nullable|date',
            'due_date' => 'nullable|date|after:invoice_date',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'payment_terms' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:2000',
            'internal_notes' => 'nullable|string|max:2000',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.purchase_detail_id' => 'nullable|exists:purchase_details,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'purchase_id.exists' => 'The selected purchase does not exist.',
            'purchase_order_id.exists' => 'The selected purchase order does not exist.',
            'due_date.after' => 'The due date must be after the invoice date.',
            'items.required' => 'At least one item is required.',
            'items.*.quantity.min' => 'The quantity must be at least 1.',
            'items.*.unit_price.min' => 'The unit price must be at least 0.',
            'file.mimes' => 'The file must be a PDF, JPG, JPEG, or PNG.',
            'file.max' => 'The file must not exceed 10MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => $this->user()->business_id ?? null,
            'branch_id' => $this->user()->branch_id ?? null,
        ]);
    }
}
