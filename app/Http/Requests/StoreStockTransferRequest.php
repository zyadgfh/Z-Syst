<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\ProductStock;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * StoreStockTransferRequest
 *
 * Form request for creating a new stock transfer.
 * Validates that the requesting user has permission to create transfers,
 * that both branches belong to the same company, and that stock is available.
 */
class StoreStockTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_branch_id' => 'required|integer|exists:branches,id',
            'to_branch_id' => 'required|integer|exists:branches,id|different:from_branch_id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.product_stock_id' => 'nullable|integer|exists:product_stocks,id',
            'items.*.quantity_requested' => 'required|numeric|min:0.01|max:999999.99',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date|after:today',
            'items.*.unit_cost' => 'required|numeric|min:0|max:999999.99',
            'items.*.notes' => 'nullable|string|max:500',
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
            'from_branch_id.required' => 'The source branch is required.',
            'from_branch_id.exists' => 'The source branch does not exist.',
            'to_branch_id.required' => 'The destination branch is required.',
            'to_branch_id.exists' => 'The destination branch does not exist.',
            'to_branch_id.different' => 'Source and destination branches must be different.',
            'items.required' => 'At least one item must be included in the transfer.',
            'items.min' => 'At least one item must be included in the transfer.',
            'items.*.product_id.required' => 'Product ID is required for each item.',
            'items.*.product_id.exists' => 'One or more products do not exist.',
            'items.*.quantity_requested.required' => 'Quantity is required for each item.',
            'items.*.quantity_requested.min' => 'Quantity must be greater than 0.',
            'items.*.unit_cost.required' => 'Unit cost is required for each item.',
            'items.*.expiry_date.after' => 'Expiry date must be in the future.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validateBranchesBelongToSameCompany($validator);
            $this->validateStockAvailability($validator);
        });
    }

    /**
     * Validate that both branches belong to the same company.
     */
    protected function validateBranchesBelongToSameCompany(Validator $validator): void
    {
        $fromBranch = Branch::find($this->from_branch_id);
        $toBranch = Branch::find($this->to_branch_id);

        if (! $fromBranch || ! $toBranch) {
            return;
        }

        if ($fromBranch->company_id !== $toBranch->company_id) {
            $validator->errors()->add('to_branch_id', 'Source and destination branches must belong to the same company.');
        }

        if ($fromBranch->company_id !== auth()->user()->company_id) {
            $validator->errors()->add('from_branch_id', 'You can only create transfers within your company.');
        }
    }

    /**
     * Validate that sufficient stock is available for transfer.
     */
    protected function validateStockAvailability(Validator $validator): void
    {
        if (! $this->items) {
            return;
        }

        foreach ($this->items as $index => $item) {
            $productStock = ProductStock::where('product_id', $item['product_id'])
                ->where('branch_id', $this->from_branch_id)
                ->where('is_active', true)
                ->first();

            if (! $productStock) {
                $validator->errors()->add("items.{$index}.quantity_requested",
                    "No stock available for product ID {$item['product_id']} in the source branch.");

                continue;
            }

            if ($productStock->quantity < $item['quantity_requested']) {
                $validator->errors()->add("items.{$index}.quantity_requested",
                    "Insufficient stock. Available: {$productStock->quantity}, Requested: {$item['quantity_requested']}.");
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
