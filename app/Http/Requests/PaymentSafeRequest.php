<?php

namespace App\Http\Requests;

class PaymentSafeRequest extends SafeRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'amount' => 'sometimes|required|numeric|min:0.01|max:999999.99',
            'currency' => 'sometimes|required|string|in:EGP,USD,EUR',
            'gateway' => 'sometimes|required|string|in:vodafone,fawry,instapay,orange,bank_card,cash',
            'transaction_id' => 'sometimes|required|string|max:255',
            'card_number' => 'sometimes|required|string|regex:/^[0-9]{16}$/',
            'card_expiry' => 'sometimes|required|string|regex:/^(0[1-9]|1[0-2])\/([0-9]{2})$/',
            'card_cvv' => 'sometimes|required|string|regex:/^[0-9]{3,4}$/',
        ]);
    }

    public function sanitize(): array
    {
        $input = parent::sanitize();

        // Sanitize amount
        if (isset($input['amount'])) {
            $input['amount'] = number_format((float) $input['amount'], 2, '.', '');
        }

        // Sanitize currency
        if (isset($input['currency'])) {
            $input['currency'] = strtoupper(trim($input['currency']));
        }

        // Sanitize gateway
        if (isset($input['gateway'])) {
            $input['gateway'] = strtolower(trim($input['gateway']));
        }

        // Sanitize card number (keep only digits)
        if (isset($input['card_number'])) {
            $input['card_number'] = preg_replace('/[^0-9]/', '', $input['card_number']);
        }

        // Sanitize card expiry
        if (isset($input['card_expiry'])) {
            $input['card_expiry'] = trim($input['card_expiry']);
        }

        // Sanitize CVV (keep only digits)
        if (isset($input['card_cvv'])) {
            $input['card_cvv'] = preg_replace('/[^0-9]/', '', $input['card_cvv']);
        }

        $this->replace($input);

        return $input;
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Amount must be at least 0.01',
            'currency.in' => 'Currency must be EGP, USD, or EUR',
            'gateway.in' => 'Invalid payment gateway',
            'card_number.regex' => 'Card number must be 16 digits',
            'card_expiry.regex' => 'Card expiry must be in MM/YY format',
            'card_cvv.regex' => 'CVV must be 3 or 4 digits',
        ];
    }
}