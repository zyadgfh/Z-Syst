<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'type' => ['required', Rule::in([
                'stock_refill',
                'prescription_ready',
                'low_stock',
                'purchase_request',
                'general',
            ])],
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
            'parent_id' => ['nullable', 'exists:messages,id'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure company_id from tenant
        $this->merge([
            'company_id' => $this->user()->company_id ?? app('tenant.company_id'),
        ]);
    }
}