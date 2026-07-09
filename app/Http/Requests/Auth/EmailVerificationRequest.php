<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseEmailVerificationRequest;

class EmailVerificationRequest extends BaseEmailVerificationRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
