<?php

namespace App\Http\Requests;

class LinkPrescriptionToSaleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prescription_id' => 'required|exists:prescriptions,id',
            'sale_id' => 'required|exists:sales,id',
            'batch_no' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
        ];
    }
}
