<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Party::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:customer,supplier,doctor,walking_customer',
            'phone' => 'required|string|max:20|unique:parties,phone',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'due' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric',
            'status' => 'nullable|in:active,inactive',
            'image' => 'nullable|image|max:2048',
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