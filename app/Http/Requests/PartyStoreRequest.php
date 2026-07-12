<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:customer,supplier',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'image' => 'nullable|string',
            'status' => 'nullable|boolean',
        ];
    }
}