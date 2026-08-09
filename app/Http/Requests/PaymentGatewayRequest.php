<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentGatewayRequest extends FormRequest
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
            'company_id' => 'required|exists:business,id',
            'branch_id' => 'nullable|exists:branches,id',
            'gateway_type' => 'required|in:vodafone_cash,bank_card,fawry,orange_cash,instapay,cash',
            'is_active' => 'boolean',
            'config_data' => 'nullable|array',
            'branch_config_data' => 'nullable|array',
            'transaction_fee' => 'nullable|numeric|min:0',
            'transaction_fee_type' => 'nullable|in:percentage,fixed',
            'sort_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'gateway_type.in' => 'Invalid gateway type selected',
            'transaction_fee_type.in' => 'Transaction fee type must be either percentage or fixed',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active') ? (bool) $this->input('is_active') : true,
            'transaction_fee_type' => $this->input('transaction_fee_type', 'percentage'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}
