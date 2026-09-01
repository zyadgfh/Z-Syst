<?php

namespace App\Http\Requests;

class UpdateMedicineTypeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:medicine_types,name,' . $this->route('medicineType')->id . ',id,business_id,' . auth()->user()->business_id,
        ];
    }
}
