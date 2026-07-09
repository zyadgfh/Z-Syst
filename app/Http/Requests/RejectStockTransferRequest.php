<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * RejectStockTransferRequest
 *
 * Form request for rejecting a stock transfer.
 * Requires a rejection reason.
 */
class RejectStockTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $transfer = $this->route('stock_transfer') ?? $this->route('stockTransfer');

        if (! $transfer) {
            return false;
        }

        // User must belong to the same company
        if ($transfer->company_id !== auth()->user()->company_id) {
            return false;
        }

        // User must have permission to reject transfers
        return auth()->user()->hasPermission('reject_stock_transfers') || auth()->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => 'required|string|min:5|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'A rejection reason is required.',
            'rejection_reason.min' => 'Rejection reason must be at least 5 characters.',
            'rejection_reason.max' => 'Rejection reason must not exceed 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $transfer = $this->route('stock_transfer') ?? $this->route('stockTransfer');

            if (! $transfer) {
                $validator->errors()->add('stock_transfer', 'Transfer not found.');

                return;
            }

            // Can only reject pending transfers
            if (! $transfer->canBeRejected()) {
                $validator->errors()->add('status',
                    "Cannot reject transfer with status: {$transfer->status}. Only pending transfers can be rejected.");
            }

            // Verify user belongs to the source branch (for branch-level rejection)
            // or has company-wide permission
            if (auth()->user()->branch_id !== $transfer->from_branch_id && ! auth()->user()->isSuperAdmin()) {
                $validator->errors()->add('authorization',
                    'You must belong to the source branch to reject this transfer.');
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
