<?php

namespace App\Http\Requests;

class UpdateManufacturerRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:manufacturers,name,' . $this->route('manufacturer')->id . ',id,business_id,' . auth()->user()->business_id,
            'description' => 'nullable|string|max:1000',
        ];
    }
}
