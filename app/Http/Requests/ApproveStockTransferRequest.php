<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * ApproveStockTransferRequest
 * 
 * Form request for approving a stock transfer.
 * Validates that the transfer is in a pending state and user has permission.
 */
class ApproveStockTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $transfer = $this->route('stock_transfer') ?? $this->route('stockTransfer');
        
        if (!$transfer) {
            return false;
        }

        // User must belong to the same company
        if ($transfer->company_id !== auth()->user()->company_id) {
            return false;
        }

        // User must have permission to approve transfers
        return auth()->user()->hasPermission('approve_stock_transfers') || auth()->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No additional fields required for approval
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $transfer = $this->route('stock_transfer') ?? $this->route('stockTransfer');
            
            if (!$transfer) {
                $validator->errors()->add('stock_transfer', 'Transfer not found.');
                return;
            }

            // Can only approve pending transfers
            if (!$transfer->canBeApproved()) {
                $validator->errors()->add('status', 
                    "Cannot approve transfer with status: {$transfer->status}. Only pending transfers can be approved.");
            }

            // Verify user belongs to the source branch (for branch-level approval)
            // or has company-wide permission
            if (auth()->user()->branch_id !== $transfer->from_branch_id && !auth()->user()->isSuperAdmin()) {
                $validator->errors()->add('authorization', 
                    'You must belong to the source branch to approve this transfer.');
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
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
