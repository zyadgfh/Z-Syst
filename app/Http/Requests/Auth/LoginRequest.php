<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'in:on'],
        ];

        // Only enforced when the Supabase project has CAPTCHA protection on;
        // otherwise the field is optional and simply not forwarded.
        if (config('supabase.captcha.enabled')) {
            $rules['captcha_token'] = ['required', 'string'];
        } else {
            $rules['captcha_token'] = ['nullable', 'string'];
        }

        return $rules;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $remember = $this->boolean('remember');

        if (! is_bool($remember)) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'remember' => trans('auth.remember_field_invalid'),
            ]);
        }

        $credentials = [
            'email' => (string) $this->input('email'),
            'password' => (string) $this->input('password'),
        ];

        if (config('supabase.captcha.enabled')) {
            $credentials['captcha_token'] = (string) $this->input('captcha_token');
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => $this->failureMessage(),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Distinguish a rejected CAPTCHA from bad credentials so the user is not
     * told their password is wrong when it is not.
     */
    protected function failureMessage(): string
    {
        $provider = Auth::getProvider();

        if ($provider instanceof \App\Auth\SupabaseUserProvider
            && $provider->lastError() === 'captcha_failed') {
            return trans('auth.captcha_failed');
        }

        return trans('auth.failed');
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}
