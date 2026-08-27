<?php

namespace App\Http\Requests;

class UpdateUnitRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessId = $this->user()->business_id;
        $unitId = $this->route('unit')?->id ?? $this->route('unit');

        return [
            'unitName' => [
                'required', 'string', 'max:255',
                'unique:units,unitName,'.$unitId.',id,business_id,'.$businessId,
            ],
        ];
    }
}
