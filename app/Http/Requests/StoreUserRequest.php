<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:admin,staff,shop-owner',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'image' => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Name is required'),
            'role.required' => __('Role is required'),
            'email.required' => __('Email is required'),
            'email.unique' => __('Email already exists'),
            'password.required' => __('Password is required'),
            'password.confirmed' => __('Password confirmation does not match'),
        ];
    }
}
