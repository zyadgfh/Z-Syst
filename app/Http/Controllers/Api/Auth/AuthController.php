<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Mobile / SPA authentication.
 *
 * PATH B: credentials are verified by Supabase GoTrue through the `supabase`
 * user provider (see App\Auth\SupabaseUserProvider). This controller no
 * longer stores password hashes, no longer keeps OTP codes in remember_token
 * and no longer writes to columns that do not exist in public.app_users.
 *
 * Account creation and e-mail confirmation are handled by Supabase:
 *  - self-service signup is disabled on the project (disable_signup = true)
 *  - users are provisioned after payment from the dashboard / Admin API
 */
class AuthController extends Controller
{
    /**
     * POST /api/v1/sign-in
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $credentials = [
            'email' => (string) $request->input('email'),
            'password' => (string) $request->input('password'),
        ];

        if (config('supabase.captcha.enabled')) {
            $credentials['captcha_token'] = (string) $request->input('captcha_token');
        }

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => __('Invalid email or password!'),
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! in_array($user->role, ['staff', 'shop-owner', 'pharmacist', 'cashier', 'manager'], true)) {
            Auth::logout();

            return response()->json([
                'message' => __('You cannot sign in as :role from the app.', ['role' => $user->role]),
            ], 403);
        }

        if (($user->status ?? 'active') !== 'active') {
            Auth::logout();

            return response()->json(['message' => __('This account is disabled.')], 403);
        }

        $device = (string) ($request->input('device_name') ?: 'api');

        return response()->json([
            'message' => __('Signed in successfully.'),
            'data' => [
                'is_setup' => (bool) $user->business_id,
                'token' => $user->createToken($device)->plainTextToken,
                'user' => [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'business_id' => $user->business_id,
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/sign-up
     *
     * Signup is closed: a pharmacy is provisioned after payment, never from a
     * public endpoint. GoTrue rejects the request anyway (disable_signup).
     */
    public function signUp(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Self-service signup is disabled. Contact support to activate your pharmacy.'),
        ], 403);
    }

    /**
     * POST /api/v1/submit-otp
     *
     * E-mail confirmation is a GoTrue concern (confirmation link), so the OTP
     * round-trip that used to live in remember_token is gone.
     */
    public function submitOtp(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('E-mail confirmation is handled by Supabase. Please use the link sent to your inbox.'),
        ], 410);
    }

    /**
     * POST /api/v1/resend-otp
     */
    public function resendOtp(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('E-mail confirmation is handled by Supabase. Please use the link sent to your inbox.'),
        ], 410);
    }

    /**
     * POST /api/v1/sign-out
     */
    public function signOut(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => __('Unauthorized')], 401);
        }

        $user->currentAccessToken()?->delete();
        Auth::guard('web')->logout();

        try {
            $token = (string) $request->bearerToken();
            if ($token !== '') {
                app(\App\Support\Supabase\SupabaseAuthClient::class)->signOut($token);
            }
        } catch (Throwable) {
            // GoTrue being unreachable must not block a local sign-out.
        }

        return response()->json(['message' => __('Signed out successfully.')]);
    }

    /**
     * POST /api/v1/refresh-token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => __('Unauthorized')], 401);
        }

        $user->currentAccessToken()?->delete();

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }
}
