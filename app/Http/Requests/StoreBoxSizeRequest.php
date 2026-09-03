<?php

namespace App\Http\Requests;

class StoreBoxSizeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:box_sizes,name,NULL,id,business_id,' . auth()->user()->business_id,
        ];
    }
}
