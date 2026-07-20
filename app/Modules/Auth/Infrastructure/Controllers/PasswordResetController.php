<?php

namespace App\Modules\Auth\Infrastructure\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException as LaravelValidationException;

class PasswordResetController
{
    /**
     * Handle forgot password request.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email|exists:users,email',
            ]);

            // Send password reset link
            $status = Password::sendResetLink(
                $validated
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'message' => 'Password reset link sent to your email',
                ]);
            }

            throw new ValidationException(['email' => trans($status)]);
        } catch (LaravelValidationException $e) {
            throw new ValidationException($e->errors(), 422);
        }
    }

    /**
     * Handle password reset.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email|exists:users,email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $status = Password::reset(
                $validated,
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'message' => 'Password reset successfully',
                ]);
            }

            throw new ValidationException(['email' => trans($status)]);
        } catch (LaravelValidationException $e) {
            throw new ValidationException($e->errors(), 422);
        }
    }

    /**
     * Change password for authenticated user.
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = $request->user();

            if (!Hash::check($validated['current_password'], $user->password)) {
                throw new ValidationException(['current_password' => 'Current password is incorrect']);
            }

            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            return response()->json([
                'message' => 'Password changed successfully',
            ]);
        } catch (LaravelValidationException $e) {
            throw new ValidationException($e->errors(), 422);
        }
    }
}
