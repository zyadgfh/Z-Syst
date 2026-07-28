<?php

namespace App\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Use Spatie RBAC for admin check
            if ($user->hasRole(['super-admin', 'admin', 'shop-owner'])) {
                return $next($request);
            }

            // Fallback: also check legacy role field for backward compatibility
            if (! empty($user->role) && ! in_array($user->role, ['shop-owner', 'staff'], true)) {
                return $next($request);
            }
        }

        // Redirect if the user is not an admin
        return redirect('/');
    }
}
