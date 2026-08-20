<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');
        return $this->user()->can('update', $campaign);
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:email,sms,push',
            'subject' => 'sometimes|required_if:type,email|nullable|string|max:255',
            'content' => 'sometimes|string',
            'template' => 'nullable|string|max:100',
            'target_segment' => 'nullable|in:all,active,inactive,high_value,due_balance',
            'scheduled_at' => 'nullable|date|after:now',
            'status' => 'sometimes|in:draft,scheduled,sent,failed',
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