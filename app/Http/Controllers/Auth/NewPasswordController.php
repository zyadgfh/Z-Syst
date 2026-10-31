<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Supabase\SupabaseAuthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Completes a Supabase recovery flow.
 *
 * The e-mailed link returns to SITE_URL with the recovery token in the URL
 * fragment (#access_token=...). The reset page copies it into a hidden field,
 * and this controller hands it to GoTrue, which performs the actual password
 * change. Nothing is written to app_users.
 */
class NewPasswordController extends Controller
{
    public function __construct(protected SupabaseAuthClient $supabase)
    {
    }

    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'access_token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
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

        $status = $ok
            ? __('Your password has been changed. You can sign in now.')
            : __('This reset link is invalid or has expired. Request a new one.');

        if ($request->wantsJson()) {
            return $ok
                ? new JsonResponse(['status' => $status, 'redirect' => route('login')])
                : new JsonResponse(['message' => $status], 422);
        }

        return $ok
            ? redirect()->route('login')->with('status', $status)
            : back()->withErrors(['password' => $status]);
    }
}
