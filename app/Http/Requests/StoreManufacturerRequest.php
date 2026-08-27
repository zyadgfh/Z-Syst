<?php

namespace App\Http\Requests;

class StoreManufacturerRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:manufacturers,name,NULL,id,business_id,' . auth()->user()->business_id,
            'description' => 'nullable|string|max:1000',
        ];
    }
}
