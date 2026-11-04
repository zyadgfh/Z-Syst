<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Supabase\SupabaseAuthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Path B: Laravel owns no password hashes, so Laravel's Password broker (which
 * needs a password_reset_tokens table) is out of the picture. The reset link is
 * issued by Supabase GoTrue and lands on SITE_URL.
 */
class PasswordResetLinkController extends Controller
{
    public function __construct(protected SupabaseAuthClient $supabase)
    {
    }

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $this->supabase->recover((string) $request->input('email'));
        } catch (Throwable $e) {
            // Never reveal whether the address exists, and never fail loudly
            // on a mail-side error.
            report($e);
        }

        $status = __('If that address exists, a password reset link is on its way.');

        return $request->wantsJson()
            ? new JsonResponse(['status' => $status])
            : back()->with('status', $status);
    }
}
