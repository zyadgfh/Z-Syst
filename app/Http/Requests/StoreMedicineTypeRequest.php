<?php

namespace App\Http\Requests;

class StoreMedicineTypeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:medicine_types,name,NULL,id,business_id,' . auth()->user()->business_id,
        ];
    }
}
