<?php

namespace App\Http\Requests;

class UpdateTaxRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'rate' => 'required_without:tax_ids|numeric|min:0|max:100',
            'tax_ids' => 'required_without:rate|array',
            'tax_ids.*' => 'integer|exists:taxes,id',
            'status' => 'nullable|boolean',
        ];
    }
}
