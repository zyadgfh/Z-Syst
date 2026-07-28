<?php

namespace App\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth()->user();

        if (! $user || ! $user->hasPermission($permission)) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to perform this action.'], 403);
        }

        return $next($request);
    }
}
