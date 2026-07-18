<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        // Create company first
        $company = Company::create([
            'name' => $data['company_name'],
            'slug' => Str::slug($data['company_name']),
            'email' => $data['email'],
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'is_active' => true,
        ]);

        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'company_id' => $company->id,
            'status' => true,
            'lang' => 'ar',
        ]);

        // Assign super-admin role
        $user->assignRole('super-admin');

        event(new Registered($user));

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load('roles.permissions'),
            'token' => $token,
            'company' => $company,
        ];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->status) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // Check 2FA
        if ($user->two_factor_secret && ! isset($data['two_factor_code'])) {
            return [
                'requires_two_factor' => true,
                'message' => 'Two-factor authentication code required.',
            ];
        }

        if ($user->two_factor_secret && isset($data['two_factor_code'])) {
            if (! $this->verifyTwoFactor($user, $data['two_factor_code'])) {
                throw ValidationException::withMessages([
                    'two_factor_code' => ['The provided two-factor code is invalid.'],
                ]);
            }
        }

        // Delete existing tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load('roles.permissions', 'company', 'branch', 'department'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function logoutAllDevices(User $user): void
    {
        $user->tokens()->delete();
    }

    public function refreshToken(User $user)
    {
        $user->currentAccessToken()->delete();
        return $user->createToken('auth-token');
    }

    public function me(User $user): array
    {
        return $user->load('roles.permissions', 'company', 'branch', 'department');
    }

    public function sendPasswordResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        return $status;
    }

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

        return $status;
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): User
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return $user->fresh();
    }

    protected function verifyTwoFactor(User $user, string $code): bool
    {
        $twoFactorService = app(TwoFactorService::class);
        return $twoFactorService->verify($user, $code);
    }
}
