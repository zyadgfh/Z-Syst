<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $party = $this->route('party');
        return $this->user()->can('update', $party);
    }

    public function rules(): array
    {
        $party = $this->route('party');

        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:customer,supplier,doctor,walking_customer',
            'phone' => [
                'required',
                'string',
                'max:20',
                \Illuminate\Validation\Rule::unique('parties', 'phone')->ignore($party->id),
            ],
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