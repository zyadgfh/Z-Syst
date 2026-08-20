<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Password;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    abstract public function authorize(): bool;

    /**
     * Get the validation rules that apply to the request.
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => __('Validation failed.'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Common validation rules for monetary amounts.
     */
    protected function monetaryRules(): array
    {
        return [
            'required',
            'numeric',
            'min:0',
            'max:999999999.99',
            'regex:/^\d+(\.\d{1,2})?$/',
        ];
    }

    /**
     * Common validation rules for positive integers.
     */
    protected function positiveIntegerRules(): array
    {
        return [
            'required',
            'integer',
            'min:1',
            'max:999999999',
        ];
    }

    /**
     * Common validation rules for dates.
     */
    protected function dateRules(): array
    {
        return [
            'required',
            'date',
            'after_or_equal:today',
        ];
    }

    /**
     * Common validation rules for phone numbers.
     */
    protected function phoneRules(): array
    {
        return [
            'nullable',
            'string',
            'min:10',
            'max:20',
            'regex:/^[0-9+\-\s()]+$/',
        ];
    }

    /**
     * Common validation rules for passwords.
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            'min:12',
            Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
        ];
    }

    /**
     * Common validation rules for email.
     */
    protected function emailRules(): array
    {
        return [
            'required',
            'email',
            'max:255',
        ];
    }

    /**
     * Common validation rules for product codes.
     */
    protected function productCodeRules(): array
    {
        return [
            'nullable',
            'string',
            'max:100',
            'unique:products,productCode,NULL,id,business_id,' . auth()->user()?->business_id,
        ];
    }

    /**
     * Common validation rules for invoice numbers.
     */
    protected function invoiceNumberRules(): array
    {
        return [
            'required',
            'string',
            'max:50',
            'unique:sales,invoiceNumber,NULL,id,business_id,' . auth()->user()?->business_id,
        ];
    }

    /**
     * Common validation rules for percentage.
     */
    protected function percentageRules(): array
    {
        return [
            'required',
            'numeric',
            'min:0',
            'max:100',
            'regex:/^\d+(\.\d{1,2})?$/',
        ];
    }

    /**
     * Common validation rules for stock quantity.
     */
    protected function stockQuantityRules(): array
    {
        return [
            'required',
            'integer',
            'min:0',
            'max:999999999',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Override in child classes if needed
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        // Override in child classes if needed
    }
}
