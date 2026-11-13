<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coarse gate for the /admin panel.
 *
 * This is the outer check only: 56 of the 58 admin controllers add a
 * `permission:<name>` gate of their own (Spatie), which is what actually decides
 * whether a given role may perform a given action. The two that do not —
 * ProfileController and TwoFactorController — are self-service by design.
 */
class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = ['shop-owner', 'staff', 'superadmin', 'admin'];
        if (Auth::check() && in_array(Auth::user()->role, $allowed, true)) {
            return $next($request);
        }

        // A denial has to be visible to whatever is calling.
        //
        // For a browser, redirecting to / keeps the old behaviour and the user
        // ends up somewhere sensible. For an API/JSON caller a 302 is actively
        // wrong: HTTP clients follow it, land on the public homepage and read a
        // 200 HTML body as "the call worked", so an unauthorised request looks
        // successful and the Flutter client silently swallows it. Those get a
        // 403 with no body details — the caller is authenticated, just not
        // allowed, and saying more only helps whoever is mapping the panel.
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                // Plain text, not a translation key: this repository has no PHP
                // language files (lang/ holds country-list JSON only), so
                // __('errors.forbidden') would put the raw key in the response.
                'message' => 'This action is not permitted for your account.',
            ], 403);
        }

        return redirect('/');
    }
}
