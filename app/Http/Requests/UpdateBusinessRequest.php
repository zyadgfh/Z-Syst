<?php

namespace App\Http\Requests;

class UpdateBusinessRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', \App\Models\Business::findOrFail($this->route('business')));
    }

    public function rules(): array
    {
        return [
            'companyName' => 'required|string|max:250',
            'address' => 'nullable|string|max:250',
            'email' => 'required|email',
            'password' => 'nullable|string|min:6',
            'phoneNumber' => 'nullable|string|max:20',
            'shopOpeningBalance' => 'nullable|numeric|min:0',
            'business_category_id' => 'required|exists:business_categories,id',
            'plan_subscribe_id' => 'nullable|exists:plans,id',
            'pictureUrl' => 'nullable|image|max:2048',
            'gateway_id' => 'nullable|exists:gateways,id',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'companyName.required' => __('Company name is required'),
            'email.required' => __('Email is required'),
            'business_category_id.required' => __('Business category is required'),
        ];
    }
}
