<?php

namespace App\Auth;

use App\Support\Supabase\SupabaseAuthClient;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Auth\UserProvider;
use Throwable;

/**
 * Eloquent provider whose credential check is delegated to Supabase GoTrue.
 *
 * Flow: retrieveByCredentials() performs the password grant, keeps the session
 * payload (access/refresh token) and returns public.app_users row for auth.users.id.
 * validateCredentials() then simply confirms the row matches the authenticated uuid.
 */
class SupabaseUserProvider implements UserProvider
{
    protected ?array $session = null;

    protected ?string $lastError = null;

    public function __construct(
        protected SupabaseAuthClient $client,
        protected string $model = \App\Models\AppUser::class,
    ) {
    }

    public function retrieveById($identifier)
    {
        return $this->newModelQuery()->find($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        // Remember-me across sessions is handled by Laravel's session cookie.
        return $this->retrieveById($identifier);
    }

    public function updateRememberToken(UserContract $user, $token): void
    {
        // app_users has no remember_token column by design.
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        $this->session = null;
        $this->lastError = null;

        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (! is_string($email) || ! is_string($password) || $email === '' || $password === '') {
            $this->lastError = 'missing_credentials';

            return null;
        }

        $captchaToken = $credentials['captcha_token'] ?? null;

        try {
            $this->session = $this->client->signInWithPassword(
                $email,
                $password,
                is_string($captchaToken) && $captchaToken !== '' ? $captchaToken : null
            );
        } catch (Throwable $e) {
            $message = $e->getMessage();
            $this->lastError = str_contains(strtolower($message), 'captcha')
                ? 'captcha_failed'
                : $message;

            return null;
        }

        $uuid = $this->session['user']['id'] ?? null;

        if (! $uuid) {
            $this->lastError = 'no_identity_in_response';

            return null;
        }

        $user = $this->newModelQuery()->find($uuid);

        if (! $user) {
            // Authenticated in GoTrue but not provisioned for any business.
            $this->lastError = 'not_provisioned_in_app_users';

            return null;
        }

        if (($user->status ?? 'active') !== 'active') {
            $this->lastError = 'account_disabled';

            return null;
        }

        return $user;
    }

    public function validateCredentials(UserContract $user, #[\SensitiveParameter] array $credentials): bool
    {
        if (! $this->session) {
            return false;
        }

        return ($this->session['user']['id'] ?? null) === $user->getAuthIdentifier();
    }

    /**
     * Session payload produced by the last successful retrieveByCredentials().
     */
    public function session(): ?array
    {
        return $this->session;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    protected function newModelQuery()
    {
        $model = $this->createModel();

        return $model->newQuery();
    }

    protected function createModel()
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class();
    }
}
