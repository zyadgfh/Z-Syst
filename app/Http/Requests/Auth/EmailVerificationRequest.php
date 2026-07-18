<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmailVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->user()) {
            return false;
        }

        if (! hash_equals(
            sha1(auth()->user()->getEmailForVerification()),
            (string) $this->route('hash')
        )) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'expires' => ['nullable', 'integer'],
            'signature' => ['nullable', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    public function fulfill()
    {
        if (! auth()->user()->hasVerifiedEmail()) {
            auth()->user()->markEmailAsVerified();

            event(new Verified(auth()->user()));
        }
    }
}
