<?php

namespace App\Http\Requests;

use App\Models\StockTransfer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * ReceiveStockTransferRequest
 *
 * Form request for receiving a stock transfer.
 * Validates that received quantities match sent quantities and handles discrepancies.
 */
class ReceiveStockTransferRequest extends FormRequest
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

        // User must have permission to receive transfers
        return auth()->user()->hasPermission('receive_stock_transfers') || auth()->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:stock_transfer_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0|max:999999.99',
            'notes' => 'nullable|string|max:1000',
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
            'items.required' => 'Items data is required for receiving.',
            'items.*.id.required' => 'Item ID is required.',
            'items.*.id.exists' => 'One or more items do not exist.',
            'items.*.quantity_received.required' => 'Quantity received is required for each item.',
            'notes.max' => 'Notes must not exceed 1000 characters.',
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

            // Can only receive in-transit transfers
            if (! $transfer->canBeReceived()) {
                $validator->errors()->add('status',
                    "Cannot receive transfer with status: {$transfer->status}. Only in-transit transfers can be received.");
            }

            // Verify user belongs to the destination branch
            if (auth()->user()->branch_id !== $transfer->to_branch_id && ! auth()->user()->isSuperAdmin()) {
                $validator->errors()->add('authorization',
                    'You must belong to the destination branch to receive this transfer.');
            }

            // Validate item quantities
            if ($this->items) {
                $this->validateReceivingQuantities($validator, $transfer);
            }
        });
    }

    /**
     * Validate that received quantities don't exceed sent quantities.
     *
     * @param  StockTransfer  $transfer
     */
    protected function validateReceivingQuantities(Validator $validator, $transfer): void
    {
        foreach ($this->items as $itemData) {
            $transferItem = $transfer->items()->find($itemData['id']);

            if (! $transferItem) {
                $validator->errors()->add("items.{$itemData['id']}",
                    "Item ID {$itemData['id']} does not belong to this transfer.");

                continue;
            }

            // Check if quantity received exceeds sent
            if ($itemData['quantity_received'] > $transferItem->quantity_sent) {
                $validator->errors()->add("items.{$itemData['id']}.quantity_received",
                    "Quantity received ({$itemData['quantity_received']}) cannot exceed sent quantity ({$transferItem->quantity_sent}).");
            }
        }
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
