<?php

namespace App\Http\Requests;

class InitiateRecallRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'nullable|exists:products,id',
            'batch_lot_number' => 'nullable|string|max:255',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => __('Recall reason is required'),
        ];
    }
}
