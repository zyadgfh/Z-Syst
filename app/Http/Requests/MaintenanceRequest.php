<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'superadmin';
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'message' => 'sometimes|required|string|max:1000',
            'estimated_completion' => 'sometimes|required|string|max:255',
            'allowed_ips' => 'sometimes|array',
            'allowed_ips.*' => 'ip',
            'allowed_users' => 'sometimes|array',
            'allowed_users.*' => 'exists:users,id',
            'ended_at' => 'sometimes|required|date|after:now',
            'scheduled_for' => 'sometimes|required|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'العنوان مطلوب',
            'message.required' => 'الرسالة مطلوبة',
            'estimated_completion.required' => 'الوقت المقدر مطلوب',
            'allowed_ips.*.ip' => 'عنوان IP غير صالح',
            'allowed_users.*.exists' => 'المستخدم غير موجود',
            'ended_at.after' => 'وقت الانتهاء يجب أن يكون في المستقبل',
            'scheduled_for.after' => 'وقت الجدولة يجب أن يكون في المستقبل',
        ];
    }
}