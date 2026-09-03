<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProfileRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:250',
            'email' => ['required', 'email', Rule::unique('users')->ignore(auth()->id())],
            'image' => 'nullable|image|mimes:jpeg,png,gif|dimensions:max_width=2000,max_height=2000|max:1048',
        ];
    }
}
