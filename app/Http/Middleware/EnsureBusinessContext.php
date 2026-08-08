<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureBusinessContext
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (empty(auth()->user()->business_id)) {
            return response()->json(['message' => 'Business context is required.'], 403);
        }

        return $next($request);
    }
}
