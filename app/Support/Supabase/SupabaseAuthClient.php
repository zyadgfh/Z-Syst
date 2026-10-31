<?php

namespace App\Support\Supabase;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin client for Supabase GoTrue (auth.users).
 *
 * Path B rule: Laravel never stores or checks password hashes itself.
 * Credentials are verified by GoTrue; the resulting JWT identifies the user
 * and public.app_users supplies business_id + role.
 */
class SupabaseAuthClient
{
    public function __construct(
        protected ?string $url = null,
        protected ?string $anonKey = null,
        protected ?int $timeout = null,
    ) {
        $this->url = rtrim($url ?: (string) config('supabase.url'), '/');
        $this->anonKey = $anonKey ?: (string) config('supabase.anon_key');
        $this->timeout = $timeout ?: (int) config('supabase.auth.timeout', 10);
    }

    /**
     * @return array{access_token:string,refresh_token:string|null,expires_in:int,user:array}
     *
     * @throws RuntimeException when GoTrue rejects the credentials
     */
    public function signInWithPassword(string $email, #[\SensitiveParameter] string $password, ?string $captchaToken = null): array
    {
        $payload = [
            'email' => $email,
            'password' => $password,
        ];

        // When CAPTCHA protection is enabled on the Supabase project, GoTrue
        // refuses the grant unless a token verified by the configured provider
        // (hCaptcha / Turnstile) is present.
        if ($captchaToken !== null && $captchaToken !== '') {
            $payload[(string) config('supabase.captcha.token_field', 'captcha_token')] = $captchaToken;
        }

        $response = $this->request()->post($this->endpoint('token').'?grant_type=password', $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Supabase auth failed: '.($response->json('error_description')
                    ?? $response->json('msg')
                    ?? $response->body())
            );
        }

        return $this->normalise($response->json());
    }

    /**
     * @throws RuntimeException
     */
    public function refresh(string $refreshToken): array
    {
        $response = $this->request()->post($this->endpoint('token').'?grant_type=refresh_token', [
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Supabase refresh failed: '.$response->body());
        }

        return $this->normalise($response->json());
    }

    public function signOut(string $accessToken): bool
    {
        return $this->request($accessToken)->post($this->endpoint('logout'))->successful();
    }

    /**
     * Current identity for a bearer token.
     */
    public function user(string $accessToken): ?array
    {
        $response = $this->request($accessToken)->get($this->endpoint('user'));

        return $response->successful() ? $response->json() : null;
    }

    protected function request(?string $accessToken = null): PendingRequest
    {
        $this->assertConfigured();

        $request = Http::withHeaders(['apikey' => $this->anonKey])
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout);

        if ($accessToken) {
            $request = $request->withToken($accessToken);
        }

        return $request;
    }

    protected function endpoint(string $key): string
    {
        return $this->url.config("supabase.auth.{$key}");
    }

    protected function assertConfigured(): void
    {
        if (! $this->url || ! $this->anonKey) {
            throw new RuntimeException('SUPABASE_URL / SUPABASE_ANON_KEY are not configured.');
        }
    }

    protected function normalise(array $payload): array
    {
        return [
            'access_token' => $payload['access_token'] ?? '',
            'refresh_token' => $payload['refresh_token'] ?? null,
            'expires_in' => (int) ($payload['expires_in'] ?? 3600),
            'user' => $payload['user'] ?? [],
            'raw' => $payload,
        ];
    }
}
