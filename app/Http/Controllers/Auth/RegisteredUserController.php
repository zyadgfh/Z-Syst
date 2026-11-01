<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Public registration is CLOSED.
 *
 * Path B moved identity to Supabase GoTrue and the project runs with
 * disable_signup = true: pharmacies are provisioned after payment (dashboard
 * or Admin API), not from a public form. The previous implementation wrote
 * email/password/remember_token columns that no longer exist in app_users and
 * minted its own OTP, so keeping it would have been a second, weaker auth path.
 */
class RegisteredUserController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $message = __('Self-service registration is disabled. Contact support to activate your pharmacy.');

        if ($request->wantsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return back()->withErrors(['email' => $message]);
    }

    public function otpResend(Request $request): RedirectResponse|JsonResponse
    {
        $message = __('E-mail confirmation is handled by Supabase. Please use the link sent to your inbox.');

        if ($request->wantsJson()) {
            return response()->json(['message' => $message], 410);
        }

        return back()->withErrors(['email' => $message]);
    }

    public function otpSubmit(Request $request): RedirectResponse|JsonResponse
    {
        $message = __('E-mail confirmation is handled by Supabase. Please use the link sent to your inbox.');

        if ($request->wantsJson()) {
            return response()->json(['message' => $message], 410);
        }

        return back()->withErrors(['email' => $message]);
    }
}
