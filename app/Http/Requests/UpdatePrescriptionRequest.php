<?php

namespace App\Http\Requests;

class UpdatePrescriptionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'party_id' => 'nullable|exists:parties,id',
            'patient_id' => 'nullable|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'notes' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'prescription_number' => 'nullable|string|max:50',
            'review_status' => 'nullable|in:pending,approved,rejected',
            'review_notes' => 'nullable|string|max:2000',
            'expires_at' => 'nullable|date',
            'patient_name' => 'nullable|string|max:255',
            'patient_phone' => 'nullable|string|max:20',
            'doctor_name' => 'nullable|string|max:255',
            'doctor_license' => 'nullable|string|max:100',
            'batch_no' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.dosage' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.instructions' => 'nullable|string|max:1000',
        ];
    }
}
