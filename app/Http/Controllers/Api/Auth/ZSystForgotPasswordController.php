<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Support\Supabase\SupabaseAuthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Password recovery for the mobile / SPA clients.
 *
 * Under Path B there is no local password to reset and no password_reset_tokens
 * table. Supabase GoTrue issues a recovery link to the user's inbox; the client
 * then exchanges the token in that link for a new password.
 *
 * The old three-step code flow (send 6-digit code -> verify code -> update the
 * password column) cannot work any more: app_users has no password column, and
 * keeping a second credential store is exactly what the cutover removed.
 */
class ZSystForgotPasswordController extends Controller
{
    public function __construct(protected SupabaseAuthClient $supabase)
    {
    }

    /**
     * POST /api/v1/send-reset-code
     *
     * Always answers 200 so the endpoint cannot be used to enumerate accounts.
     */
    public function sendResetCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $this->supabase->recover((string) $request->input('email'));
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => __('If that address exists, a password reset link is on its way.'),
        ]);
    }

    /**
     * POST /api/v1/verify-reset-code
     *
     * Verification happens when the recovery link is opened, so there is no
     * server-side code to check.
     */
    public function verifyResetCode(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Open the reset link sent to your e-mail to continue.'),
            'deprecated' => true,
        ], 410);
    }

    /**
     * POST /api/v1/password-reset
     *
     * Accepts the recovery access token from the e-mailed link plus the new
     * password, and lets GoTrue perform the change.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'access_token' => 'required|string',
            'password' => 'required|string|min:10|confirmed',
        ]);

        try {
            $ok = $this->supabase->updatePassword(
                (string) $request->input('access_token'),
                (string) $request->input('password')
            );
        } catch (Throwable $e) {
            report($e);
            $ok = false;
        }

        if (! $ok) {
            return response()->json([
                'message' => __('This reset link is invalid or has expired. Request a new one.'),
            ], 422);
        }

        return response()->json([
            'message' => __('Your password has been changed.'),
        ]);
    }
}
