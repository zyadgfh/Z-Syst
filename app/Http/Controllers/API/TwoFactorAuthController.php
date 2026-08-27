<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorAuthController extends Controller
{
    public function __construct(
        private TwoFactorAuthService $twoFactorService
    ) {}

    /**
     * Get 2FA status for the authenticated user.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $twoFactor = $user->twoFactorAuth;

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => [
                'is_enabled' => $twoFactor && $twoFactor->is_enabled,
                'enabled_at' => $twoFactor?->enabled_at,
                'last_used_at' => $twoFactor?->last_used_at,
                'recovery_codes_remaining' => $twoFactor && $twoFactor->recovery_codes
                    ? count(json_decode($twoFactor->recovery_codes, true))
                    : 0,
            ],
        ]);
    }

    /**
     * Start 2FA setup — generate secret and return QR code data.
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($this->twoFactorService->isEnabled($user)) {
            return response()->json([
                'message' => __('Two-factor authentication is already enabled.'),
            ], 409);
        }

        $result = $this->twoFactorService->generateSecret($user);
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes($user);

        return response()->json([
            'message' => __('Scan the QR code with your authenticator app, then verify with a code.'),
            'data' => [
                'secret' => $result['secret'],
                'otpauth_url' => $result['otpauth_url'],
                'recovery_codes' => $recoveryCodes,
            ],
        ]);
    }

    /**
     * Confirm 2FA setup by verifying a code from the authenticator app.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($this->twoFactorService->isEnabled($user)) {
            return response()->json([
                'message' => __('Two-factor authentication is already enabled.'),
            ], 409);
        }

        $enabled = $this->twoFactorService->enable($user, $request->input('code'));

        if (!$enabled) {
            return response()->json([
                'message' => __('Invalid verification code. Please try again.'),
            ], 422);
        }

        return response()->json([
            'message' => __('Two-factor authentication has been enabled successfully.'),
        ]);
    }

    /**
     * Verify a 2FA code during login.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        $code = $request->input('code');

        // Try TOTP code first, then recovery code
        $valid = $this->twoFactorService->verifyCode($user, $code)
            || $this->twoFactorService->verifyRecoveryCode($user, $code);

        if (!$valid) {
            return response()->json([
                'message' => __('Invalid verification code.'),
            ], 422);
        }

        // Mark the session as 2FA verified
        $request->session()->put('2fa_verified', true);

        return response()->json([
            'message' => __('Verification successful.'),
        ]);
    }

    /**
     * Disable 2FA (requires password confirmation).
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!$this->twoFactorService->isEnabled($user)) {
            return response()->json([
                'message' => __('Two-factor authentication is not enabled.'),
            ], 404);
        }

        $disabled = $this->twoFactorService->disable($user, $request->input('password'));

        if (!$disabled) {
            return response()->json([
                'message' => __('Incorrect password.'),
            ], 422);
        }

        return response()->json([
            'message' => __('Two-factor authentication has been disabled.'),
        ]);
    }

    /**
     * Regenerate recovery codes (requires password confirmation).
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!\Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => __('Incorrect password.'),
            ], 422);
        }

        $codes = $this->twoFactorService->generateRecoveryCodes($user);

        return response()->json([
            'message' => __('Recovery codes regenerated successfully. Save these codes securely — they will not be shown again.'),
            'data' => [
                'recovery_codes' => $codes,
            ],
        ]);
    }
}
