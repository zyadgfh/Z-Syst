<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', \App\Models\User::findOrFail($this->route('user')));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:admin,staff,shop-owner',
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email,' . $this->route('user'),
            'password' => 'nullable|string|confirmed|min:8',
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
        ];
    }
}
