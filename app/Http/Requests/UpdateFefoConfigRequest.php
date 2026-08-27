<?php

namespace App\Http\Requests;

class UpdateFefoConfigRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fefo_enabled' => 'nullable|boolean',
            'deduction_mode' => 'nullable|in:automatic,manual_suggestion',
            'expiry_grace_days' => 'nullable|integer|min:1|max:365',
            'auto_deduct_expired_stock' => 'nullable|boolean',
            'notify_on_fefo_deduction' => 'nullable|boolean',
            'min_stock_for_fefo' => 'nullable|integer|min:0',
        ];
    }
}
