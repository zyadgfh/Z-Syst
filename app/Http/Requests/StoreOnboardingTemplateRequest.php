<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOnboardingTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('createTemplate', \App\Models\OnboardingTemplate::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:onboarding_templates,code',
            'description' => 'nullable|string|max:1000',
            'steps' => 'nullable|array',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.action' => 'nullable|string|max:100',
            'steps.*.description' => 'nullable|string|max:1000',
            'default_settings' => 'nullable|array',
            'default_roles' => 'nullable|array',
            'default_permissions' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
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