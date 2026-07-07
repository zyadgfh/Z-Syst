<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\EmailVerificationRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function __construct(private AuthService $authService)
    {
    }

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
}
