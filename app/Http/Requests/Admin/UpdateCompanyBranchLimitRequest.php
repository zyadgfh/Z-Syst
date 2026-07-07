<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyBranchLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'max_branches' => ['nullable', 'integer', 'min:0'],
            'is_unlimited_branches' => ['boolean'],
            'reset_to_default' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_branches.integer' => 'The branch limit must be a number.',
            'max_branches.min' => 'The branch limit cannot be negative.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_unlimited_branches')) {
            $this->merge([
                'is_unlimited_branches' => filter_var($this->is_unlimited_branches, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('reset_to_default')) {
            $this->merge([
                'reset_to_default' => filter_var($this->reset_to_default, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
