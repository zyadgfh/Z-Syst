<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => __('validation.required', ['attribute' => 'current password']),
            'current_password.current_password' => __('The current password is incorrect.'),
            'new_password.required' => __('validation.required', ['attribute' => 'new password']),
            'new_password.min' => __('validation.min.string', ['attribute' => 'new password', 'min' => 8]),
            'new_password.confirmed' => __('validation.confirmed', ['attribute' => 'new password']),
        ];
    }
}