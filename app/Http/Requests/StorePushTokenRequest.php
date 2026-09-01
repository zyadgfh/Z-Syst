<?php

namespace App\Http\Requests;

class StorePushTokenRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string|max:512',
            'platform' => 'nullable|string|in:web,android,ios',
        ];
    }
}
