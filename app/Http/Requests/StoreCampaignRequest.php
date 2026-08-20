<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Campaign::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,sms,push',
            'subject' => 'required_if:type,email|nullable|string|max:255',
            'content' => 'required|string',
            'template' => 'nullable|string|max:100',
            'target_segment' => 'nullable|in:all,active,inactive,high_value,due_balance',
            'scheduled_at' => 'nullable|date|after:now',
            'metadata' => 'nullable|array',
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