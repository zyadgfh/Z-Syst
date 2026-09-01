<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Only allow shop-owner and staff (admin-level roles) to access admin routes
        $adminRoles = ['superadmin', 'admin', 'shop-owner', 'staff'];

        if (in_array($user->role, $adminRoles)) {
            return $next($request);
        }

        // Redirect non-admin users to homepage
        return redirect('/')->with('error', __('You do not have admin access.'));
    }
}
