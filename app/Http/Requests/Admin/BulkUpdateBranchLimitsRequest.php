<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateBranchLimitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'updates' => ['required', 'array'],
            'updates.*.company_id' => ['required', 'integer', 'exists:companies,id'],
            'updates.*.max_branches' => ['nullable', 'integer', 'min:0'],
            'updates.*.is_unlimited_branches' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'updates.required' => 'At least one company update is required.',
            'updates.*.company_id.required' => 'Company ID is required for each update.',
            'updates.*.company_id.exists' => 'One or more companies do not exist.',
            'updates.*.max_branches.integer' => 'Branch limit must be a number.',
            'updates.*.max_branches.min' => 'Branch limit cannot be negative.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('updates')) {
            $updates = $this->input('updates', []);

            foreach ($updates as $index => $update) {
                if (isset($update['is_unlimited_branches'])) {
                    $updates[$index]['is_unlimited_branches'] = filter_var($update['is_unlimited_branches'], FILTER_VALIDATE_BOOLEAN);
                }
            }

            $this->merge(['updates' => $updates]);
        }
    }
}
