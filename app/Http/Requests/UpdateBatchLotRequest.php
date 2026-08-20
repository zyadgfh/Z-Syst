<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBatchLotRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->business_id !== null;
    }

    public function rules(): array
    {
        return [
            'batch_number' => ['sometimes', 'string', 'max:100'],
            'lot_number' => ['nullable', 'string', 'max:100'],
            'manufacture_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expiry_date' => ['sometimes', 'date', 'after:manufacture_date'],
            'recall_date' => ['nullable', 'date', 'after:manufacture_date'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'manufacture_date.before_or_equal' => __('Manufacture date cannot be in the future.'),
            'expiry_date.after' => __('Expiry date must be after manufacture date.'),
            'recall_date.after' => __('Recall date must be after manufacture date.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'batch_number' => __('Batch Number'),
            'lot_number' => __('Lot Number'),
            'manufacture_date' => __('Manufacture Date'),
            'expiry_date' => __('Expiry Date'),
            'recall_date' => __('Recall Date'),
            'supplier_name' => __('Supplier Name'),
            'notes' => __('Notes'),
        ];
    }
}
