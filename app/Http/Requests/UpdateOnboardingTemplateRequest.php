<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOnboardingTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('template');
        return $this->user()->can('updateTemplate', $template);
    }

    public function rules(): array
    {
        $template = $this->route('template');

        return [
            'name' => 'sometimes|string|max:255',
            'code' => [
                'sometimes',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('onboarding_templates', 'code')->ignore($template->id),
            ],
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