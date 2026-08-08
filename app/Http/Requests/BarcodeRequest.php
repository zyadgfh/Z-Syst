<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BarcodeRequest extends FormRequest
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
            'product_id' => 'nullable|exists:products,id',
            'batch_id' => 'nullable|exists:stock_batches,id',
            'barcode_number' => 'nullable|string|max:50|unique:barcodes,barcode_number,' . $this->route('barcode'),
            'barcode_type' => 'required|in:CODE128,EAN13,UPC,QR',
            'size' => 'nullable|in:small,standard,large',
            'print_settings' => 'nullable|array',
            'print_settings.show_product_name' => 'nullable|boolean',
            'print_settings.show_price' => 'nullable|boolean',
            'print_settings.show_expiry' => 'nullable|boolean',
            'print_settings.show_batch' => 'nullable|boolean',
            'print_settings.font_size' => 'nullable|integer|min:8|max:24',
            'print_settings.margin' => 'nullable|integer|min:0|max:20',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_id.exists' => 'The selected product does not exist.',
            'batch_id.exists' => 'The selected batch does not exist.',
            'barcode_number.unique' => 'This barcode number is already in use.',
            'barcode_type.in' => 'The barcode type must be one of: CODE128, EAN13, UPC, QR.',
            'size.in' => 'The size must be one of: small, standard, large.',
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
