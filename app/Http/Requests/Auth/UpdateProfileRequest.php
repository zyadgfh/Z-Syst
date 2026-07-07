<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', "unique:users,email,{$userId}"],
            'phone' => ['nullable', 'string', 'max:20'],
            'username' => ['nullable', 'string', 'max:255', "unique:users,username,{$userId}"],
            'profile_photo' => ['nullable', 'string', 'max:2048'],
            'job_title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => __('validation.string', ['attribute' => 'name']),
            'email.email' => __('validation.email'),
            'email.unique' => __('validation.unique', ['attribute' => 'email']),
            'username.unique' => __('validation.unique', ['attribute' => 'username']),
        ];
    }
}