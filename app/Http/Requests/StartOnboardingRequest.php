<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StartOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('start', \App\Models\TenantOnboardingInstance::class);
    }

    public function rules(): array
    {
        return [
            'template_id' => 'nullable|exists:onboarding_templates,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}