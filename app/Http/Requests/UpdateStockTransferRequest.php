<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * UpdateStockTransferRequest
 *
 * Form request for updating stock transfer details.
 * Currently only allows updating notes when status is pending.
 */
class UpdateStockTransferRequest extends FormRequest
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

        // Only allow updates if transfer belongs to user's company
        return $transfer->company_id === auth()->user()->company_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $transfer = $this->route('stock_transfer') ?? $this->route('stockTransfer');

        return [
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

            // Only allow updating notes if transfer is still pending
            if ($transfer && $transfer->status !== 'pending') {
                $validator->errors()->add('status', 'Can only update notes when transfer status is pending.');
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
