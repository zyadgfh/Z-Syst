<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

class AuthService extends BaseService
{
    /**
     * Register a new user.
     */
    public function register(array $data): User
    {
        $existingUser = User::where('email', $data['email'])->first();

        if ($existingUser) {
            throw ApiException::conflict('Email already registered');
        }

        $data['password'] = Hash::make($data['password']);
        $data['role'] = $data['role'] ?? 'user';
        $data['is_active'] = true;

        /** @var User $user */
        $user = User::query()->create($data);

        event(new Registered($user));

        return $user;
    }

    /**
     * Authenticate a user and return token.
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ApiException::unauthorized('Invalid email or password');
        }

        $isActive = true;
        if (isset($user->is_active)) {
            $isActive = (bool) $user->is_active;
        } elseif (isset($user->status)) {
            $isActive = $user->status === 'active';
        }

        if (! $isActive) {
            throw ApiException::forbidden('Account is deactivated');
        }

        if ($user->two_factor_confirmed_at) {
            if (empty($credentials['two_factor_code'])) {
                throw ApiException::unauthorized('Two-factor authentication code is required.');
            }

            $twoFactorService = app(TwoFactorService::class);

            if (! $twoFactorService->verify($user, $credentials['two_factor_code'])) {
                throw ApiException::unauthorized('Invalid two-factor authentication code.');
            }
        }

        $token = $user->createToken(
            $credentials['device_name'] ?? request()->userAgent() ?? 'api-token',
            $credentials['abilities'] ?? ['*']
        );

        $user->update([
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ]);

        return [
            'user' => $user->load(['company', 'branch', 'department']),
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
        ];
    }

    public function loginWithTwoFactor(array $credentials): array
    {
        return $this->login($credentials);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Logout the user (revoke current token).
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Logout from all devices (revoke all tokens).
     */
    public function logoutAllDevices(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Get authenticated user with relations.
     */
    public function me(User $user): User
    {
        return $user->load([
            'company',
            'branch',
            'department',
            'roles.permissions',
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh()->load(['company', 'branch', 'department']);
    }

    /**
     * Change user password.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): User
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ApiException::badRequest('Current password is incorrect');
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return $user->fresh();
    }

    /**
     * Send password reset link.
     */
    public function sendPasswordResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ApiException::badRequest(__($status));
        }

        return __($status);
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(array $data): string
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ApiException::badRequest(__($status));
        }

        return __($status);
    }

    /**
     * Refresh token (create new, delete old).
     */
    public function refreshToken(User $user): NewAccessToken
    {
        $user->currentAccessToken()->delete();

        return $user->createToken('api-token');
    }
}