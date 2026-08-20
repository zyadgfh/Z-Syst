<?php

namespace App\Http\Requests;

class StoreLoyaltyProgramRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\LoyaltyProgram::class);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'points_per_currency' => 'required|integer|min:1|max:1000',
            'min_points_for_reward' => 'required|integer|min:1|max:100000',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Program name is required'),
            'points_per_currency.required' => __('Points per currency is required'),
            'min_points_for_reward.required' => __('Minimum points for reward is required'),
        ];
    }
}
