<?php

namespace App\Http\Requests;

use App\Models\ProductStock;
use App\Models\StockTransfer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * ShipStockTransferRequest
 *
 * Form request for shipping a stock transfer.
 * Validates that items have been properly allocated and quantities are set.
 */
class ShipStockTransferRequest extends FormRequest
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

        // User must have permission to ship transfers
        return auth()->user()->hasPermission('ship_stock_transfers') || auth()->user()->isSuperAdmin();
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
            'items.*.quantity_sent' => 'required|numeric|min:0|max:999999.99',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date|after:today',
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
            'items.required' => 'Items data is required for shipping.',
            'items.*.id.required' => 'Item ID is required.',
            'items.*.id.exists' => 'One or more items do not exist.',
            'items.*.quantity_sent.required' => 'Quantity sent is required for each item.',
            'items.*.quantity_sent.max' => 'Quantity sent cannot exceed requested quantity.',
            'items.*.expiry_date.after' => 'Expiry date must be in the future.',
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

            // Can only ship approved transfers
            if (! $transfer->canBeShipped()) {
                $validator->errors()->add('status',
                    "Cannot ship transfer with status: {$transfer->status}. Only approved transfers can be shipped.");
            }

            // Verify user belongs to the source branch
            if (auth()->user()->branch_id !== $transfer->from_branch_id && ! auth()->user()->isSuperAdmin()) {
                $validator->errors()->add('authorization',
                    'You must belong to the source branch to ship this transfer.');
            }

            // Validate item quantities
            if ($this->items) {
                $this->validateShippingQuantities($validator, $transfer);
            }
        });
    }

    /**
     * Validate that shipping quantities don't exceed requested quantities
     * and that sufficient stock is still available.
     *
     * @param  StockTransfer  $transfer
     */
    protected function validateShippingQuantities(Validator $validator, $transfer): void
    {
        foreach ($this->items as $itemData) {
            $transferItem = $transfer->items()->find($itemData['id']);

            if (! $transferItem) {
                $validator->errors()->add("items.{$itemData['id']}",
                    "Item ID {$itemData['id']} does not belong to this transfer.");

                continue;
            }

            // Check if quantity sent exceeds requested
            if ($itemData['quantity_sent'] > $transferItem->quantity_requested) {
                $validator->errors()->add("items.{$itemData['id']}.quantity_sent",
                    "Quantity sent ({$itemData['quantity_sent']}) cannot exceed requested quantity ({$transferItem->quantity_requested}).");
            }

            // Check if sufficient stock is still available
            $productStock = ProductStock::where('product_id', $transferItem->product_id)
                ->where('branch_id', $transfer->from_branch_id)
                ->where('is_active', true)
                ->first();

            if (! $productStock || $productStock->quantity < $itemData['quantity_sent']) {
                $available = $productStock ? $productStock->quantity : 0;
                $validator->errors()->add("items.{$itemData['id']}.quantity_sent",
                    "Insufficient stock. Available: {$available}, Attempting to ship: {$itemData['quantity_sent']}.");
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
