<?php

namespace App\Services;

use App\Exceptions\Supabase\SupabaseAuthException;
use App\Logging\StructuredLogger;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Supabase\Gotrue\Client as GotrueClient;

class SupabaseAuthService
{
    protected ?GotrueClient $gotrueClient;
    protected ?string $url;
    protected ?string $key;

    public function __construct()
    {
        $this->url = config('supabase.url');
        $this->key = config('supabase.key');

        // Only initialize if Supabase is properly configured
        if ($this->url && $this->key && class_exists('Supabase\Gotrue\Client')) {
            $this->gotrueClient = new GotrueClient(
                $this->url,
                $this->key
            );
        }
    }

    /**
     * Register a new user with Supabase Auth.
     */
    public function register(string $email, string $password, array $metadata = []): array
    {
        try {
            $response = $this->gotrueClient->signUp([
                'email' => $email,
                'password' => $password,
                'data' => $metadata,
            ]);

            return [
                'success' => true,
                'user' => $response['user'] ?? null,
                'session' => $response['session'] ?? null,
                'access_token' => $response['access_token'] ?? null,
            ];
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('register', ['email' => $email], $e);
            throw new SupabaseAuthException(
                'Registration failed',
                ['email' => $email],
                $e
            );
        }
    }

    /**
     * Login user with Supabase Auth.
     */
    public function login(string $email, string $password): array
    {
        try {
            $response = $this->gotrueClient->signInWithPassword([
                'email' => $email,
                'password' => $password,
            ]);

            return [
                'success' => true,
                'user' => $response['user'] ?? null,
                'session' => $response['session'] ?? null,
                'access_token' => $response['access_token'] ?? null,
            ];
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('login', ['email' => $email], $e);
            throw new SupabaseAuthException(
                'Login failed',
                ['email' => $email],
                $e
            );
        }
    }

    /**
     * Logout user from Supabase Auth.
     */
    public function logout(string $accessToken): bool
    {
        try {
            $this->gotrueClient->signOut($accessToken);
            return true;
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('logout', [], $e);
            throw new SupabaseAuthException(
                'Logout failed',
                [],
                $e
            );
        }
    }

    /**
     * Refresh Supabase session.
     */
    public function refreshSession(string $refreshToken): array
    {
        try {
            $response = $this->gotrueClient->refreshSession($refreshToken);

            return [
                'success' => true,
                'access_token' => $response['access_token'] ?? null,
                'refresh_token' => $response['refresh_token'] ?? null,
                'user' => $response['user'] ?? null,
            ];
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('refresh_session', [], $e);
            throw new SupabaseAuthException(
                'Session refresh failed',
                [],
                $e
            );
        }
    }

    /**
     * Get current user from Supabase.
     */
    public function getCurrentUser(string $accessToken): array
    {
        try {
            $response = $this->gotrueClient->getUser($accessToken);

            return [
                'success' => true,
                'user' => $response,
            ];
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('get_user', [], $e);
            throw new SupabaseAuthException(
                'Failed to get user',
                [],
                $e
            );
        }
    }

    /**
     * Update user metadata in Supabase.
     */
    public function updateUser(string $accessToken, array $attributes): array
    {
        try {
            $response = $this->gotrueClient->updateUser($accessToken, $attributes);

            return [
                'success' => true,
                'user' => $response,
            ];
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('update_user', [], $e);
            throw new SupabaseAuthException(
                'Failed to update user',
                [],
                $e
            );
        }
    }

    /**
     * Send password reset email.
     */
    public function sendPasswordReset(string $email): array
    {
        try {
            $response = $this->gotrueClient->resetPasswordForEmail($email, [
                'redirectTo' => route('password.reset'),
            ]);

            return [
                'success' => true,
                'message' => 'Password reset email sent',
            ];
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('send_password_reset', ['email' => $email], $e);
            throw new SupabaseAuthException(
                'Failed to send password reset',
                ['email' => $email],
                $e
            );
        }
    }

    /**
     * Update user password.
     */
    public function updatePassword(string $accessToken, string $newPassword): array
    {
        try {
            $response = $this->gotrueClient->updateUser($accessToken, [
                'password' => $newPassword,
            ]);

            return [
                'success' => true,
                'user' => $response,
            ];
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('update_password', [], $e);
            throw new SupabaseAuthException(
                'Failed to update password',
                [],
                $e
            );
        }
    }

    /**
     * Link Supabase user with Laravel user.
     */
    public function linkWithLaravelUser(string $supabaseUserId, int $laravelUserId): bool
    {
        try {
            $user = User::find($laravelUserId);
            if (!$user) {
                return false;
            }

            $user->update([
                'supabase_id' => $supabaseUserId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Supabase-Laravel link error', [
                'supabase_id' => $supabaseUserId,
                'laravel_id' => $laravelUserId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verify Supabase JWT token.
     */
    public function verifyToken(string $token): array
    {
        try {
            $decoded = $this->gotrueClient->verifyToken($token);

            return [
                'success' => true,
                'user' => $decoded,
            ];
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('verify_token', [], $e);
            throw new SupabaseAuthException(
                'Token verification failed',
                [],
                $e
            );
        }
    }
}