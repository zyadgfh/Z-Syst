<?php

namespace App\Http\Requests;

class DestroyPushTokenRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string|max:512',
        ];
    }
}
