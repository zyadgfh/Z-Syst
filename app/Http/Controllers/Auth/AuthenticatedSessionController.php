<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\BusinessCategory;
use App\Models\Option;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        $login_page = Option::where('key', 'login-page')->first();
        $business_categories = BusinessCategory::latest()->get();

        return view('auth.login', compact('login_page', 'business_categories'));
    }

    /**
     * Handle an incoming authentication request — with TOTP 2FA if enabled.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $user = auth()->user();
        // Enforce TOTP if user has it enabled
        if ($user && $user->two_factor_secret) {
            $code = $request->input('code') ?? $request->input('otp') ?? $request->input('two_factor_code');
            if (! $code) {
                Auth::logout();
                return $request->wantsJson()
                    ? response()->json(['requires_2fa' => true, 'message' => __('رمز التحقق بخطوتين مطلوب')], 423)
                    : back()->withErrors(['code' => __('رمز التحقق بخطوتين مطلوب')]);
            }
            if (! \App\Services\Totp::verify($user->two_factor_secret, (string) $code)) {
                Auth::logout();
                return $request->wantsJson()
                    ? response()->json(['message' => __('رمز التحقق غير صحيح')], 422)
                    : back()->withErrors(['code' => __('رمز التحقق غير صحيح')]);
            }
        }

        $request->session()->regenerate();

        $remember = $request->filled('remember') ? 1 : 0;
        $redirect_url = url('/');
        $user = auth()->user();

        if ($user->role == 'shop-owner' || $user->role == 'staff') {
            Auth::logout();

            return response()->json([
                'redirect' => route('login'),
                'message' => __('You can not login as an business account right now.'),
            ]);
        } else {
            // Defensive: a seeded/synced user may not have a matching spatie
            // Role row (e.g. local fallback superadmin). Fall back to the
            // admin dashboard instead of 500ing on null->permissions.
            $role = Role::where('name', $user->role)->first();
            $first_role = $role?->permissions->pluck('name')->all()[0] ?? null;

            if ($first_role) {
                $page = explode('-', $first_role);
                $redirect_url = route('admin.'.$page[0].'.index');
            } else {
                $redirect_url = route('admin.dashboard.index');
            }
        }

        if (! $request->wantsJson()) {
            // Classic web form: land on the dashboard (which routes into the
            // admin panel). The SPA consumes the JSON payload above instead.
            return redirect('/dashboard');
        }

        return response()->json([
            'message' => __('Logged In Successfully'),
            'remember' => $remember,
            'redirect' => $redirect_url,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
