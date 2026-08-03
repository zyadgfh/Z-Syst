<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'search' => 'nullable|string|max:255',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'payment_status' => 'nullable|in:paid,unpaid',
            'party_id' => 'nullable|integer|exists:parties,id',
            'status' => 'nullable|string|max:50',
            'audit_type' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
        ];
    }

    protected function prepareForValidation()
    {
        // Trim string inputs
        if ($this->has('search')) {
            $this->merge(['search' => trim($this->input('search'))]);
        }
    }
}
