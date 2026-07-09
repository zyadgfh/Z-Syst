<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * CancelStockTransferRequest
 *
 * Form request for cancelling a stock transfer.
 * Only allows cancellation of pending or approved transfers.
 */
class CancelStockTransferRequest extends FormRequest
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

        // User must have permission to cancel transfers
        return auth()->user()->hasPermission('cancel_stock_transfers') || auth()->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cancellation_reason' => 'nullable|string|min:5|max:1000',
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
            'cancellation_reason.min' => 'Cancellation reason must be at least 5 characters.',
            'cancellation_reason.max' => 'Cancellation reason must not exceed 1000 characters.',
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

            // Can only cancel pending or approved transfers
            if (! $transfer->canBeCancelled()) {
                $validator->errors()->add('status',
                    "Cannot cancel transfer with status: {$transfer->status}. Only pending or approved transfers can be cancelled.");
            }

            // Verify user belongs to either the source or destination branch
            // or has company-wide permission
            $userBranchId = auth()->user()->branch_id;
            if ($userBranchId !== $transfer->from_branch_id
                && $userBranchId !== $transfer->to_branch_id
                && ! auth()->user()->isSuperAdmin()) {
                $validator->errors()->add('authorization',
                    'You must belong to either the source or destination branch to cancel this transfer.');
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
