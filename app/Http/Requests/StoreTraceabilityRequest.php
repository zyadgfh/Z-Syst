<?php

namespace App\Http\Requests;

class StoreTraceabilityRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\BatchLot::class);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'product_id' => 'required|exists:products,id',
            'batch_number' => 'nullable|string|max:255',
            'lot_number' => 'nullable|string|max:255',
            'manufacture_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:manufacture_date',
            'supplier_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => __('Product is required'),
            'expiry_date.after' => __('Expiry date must be after manufacture date'),
        ];
    }
}
