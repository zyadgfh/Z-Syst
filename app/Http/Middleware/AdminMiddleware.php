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
        $allowed = ['shop-owner', 'staff', 'superadmin', 'admin'];
        if (Auth::check() && in_array(Auth::user()->role, $allowed, true)) {
            return $next($request);
        }

        // Redirect if the user is not an admin
        return redirect('/');
    }
}
