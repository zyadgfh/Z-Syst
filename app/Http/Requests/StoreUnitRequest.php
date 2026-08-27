<?php

namespace App\Http\Requests;

class StoreUnitRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessId = $this->user()->business_id;

        return [
            'unitName' => 'required|string|max:255|unique:units,unitName,NULL,id,business_id,'.$businessId,
        ];
    }
}
