<?php

namespace App\Http\Requests;

class StoreLanguageRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lang' => 'required|string|min:1|max:30',
        ];
    }
}
