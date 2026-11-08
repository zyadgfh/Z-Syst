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
        // Local fallback mode (dev / CI without Supabase GoTrue): provision the
        // account directly from the form. Production keeps self-registration
        // closed — GoTrue has disable_signup and pharmacies are provisioned
        // after payment.
        if (config('supabase.fallback_eloquent')) {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:190',
                'password' => 'required|string|min:8|confirmed',
                'business_id' => 'nullable|integer|exists:businesses,id',
            ]);

            $user = \App\Models\User::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                'business_id' => $data['business_id'] ?? null,
                'role' => 'admin',
                'status' => 'active',
            ]);

            auth()->login($user);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => __('Registered successfully.'),
                    'user' => $user->only('id', 'name', 'email'),
                ], 201);
            }

            return redirect('/dashboard');
        }

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
