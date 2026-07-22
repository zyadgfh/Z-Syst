<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Requests\Auth\BarcodeLoginRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\EmailVerificationRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\TwoFactorSetupRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function __construct(private AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return $this->created($user, 'Registered successfully');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->success($result, 'Logged in successfully');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'Logged out successfully');
    }

    public function logoutAllDevices(Request $request): JsonResponse
    {
        $this->authService->logoutAllDevices($request->user());

        return $this->success(null, 'Logged out from all devices successfully');
    }

    public function refresh(Request $request): JsonResponse
    {
        $token = $this->authService->refreshToken($request->user());

        return $this->success([
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
        ], 'Token refreshed successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($this->authService->me($request->user()));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->sendPasswordResetLink($request->validated()['email']);

        return $this->success(['status' => $status], 'Password reset link sent');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->resetPassword($request->validated());

        return $this->success(['status' => $status], 'Password has been reset successfully');
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());

        return $this->success($user, 'Profile updated successfully');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $this->authService->changePassword(
            $request->user(),
            $request->validated()['current_password'],
            $request->validated()['new_password']
        );

        return $this->success($user, 'Password changed successfully');
    }

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success(null, 'Email already verified');
        }

        $user->sendEmailVerificationNotification();

        return $this->success(null, 'Verification email sent successfully');
    }

    public function verifyEmail(EmailVerificationRequest $request): JsonResponse
    {
        $request->fulfill();

        return $this->success(null, 'Email verified successfully');
    }

    public function setupTwoFactor(Request $request): JsonResponse
    {
        $twoFactorService = app(\App\Services\TwoFactorService::class);
        $data = $twoFactorService->generateSecret($request->user());

        return $this->success($data, 'Two-factor setup initiated');
    }

    public function enableTwoFactor(TwoFactorSetupRequest $request): JsonResponse
    {
        $twoFactorService = app(\App\Services\TwoFactorService::class);
        $enabled = $twoFactorService->enable($request->user(), $request->code);

        if (! $enabled) {
            return $this->error('Invalid verification code', 422);
        }

        return $this->success(null, 'Two-factor authentication enabled');
    }

    public function disableTwoFactor(TwoFactorSetupRequest $request): JsonResponse
    {
        $twoFactorService = app(\App\Services\TwoFactorService::class);
        $disabled = $twoFactorService->disable($request->user(), $request->code);

        if (! $disabled) {
            return $this->error('Invalid verification code', 422);
        }

        return $this->success(null, 'Two-factor authentication disabled');
    }

    public function regenerateRecoveryCodes(TwoFactorSetupRequest $request): JsonResponse
    {
        $twoFactorService = app(\App\Services\TwoFactorService::class);
        $codes = $twoFactorService->regenerateRecoveryCodes($request->user(), $request->code);

        return $this->success(['recovery_codes' => $codes], 'Recovery codes regenerated');
    }

    public function barcodeLogin(BarcodeLoginRequest $request): JsonResponse
    {
        // Find user by their barcode field (not email)
        $user = User::where('barcode', $request->barcode)->first();

        if (! $user) {
            return $this->error('Invalid barcode', 401);
        }

        if (! $user->status) {
            return $this->error('Account deactivated', 403);
        }

        // Set branch if provided
        if ($request->branch_id) {
            $user->branch_id = $request->branch_id;
            $user->save();
        }

        // Create token
        $token = $user->createToken('barcode-token')->plainTextToken;

        return $this->success([
            'user' => $user->load('roles.permissions', 'company', 'branch', 'department'),
            'token' => $token,
        ], 'Logged in successfully via barcode');
    }
}
