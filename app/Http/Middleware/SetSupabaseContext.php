<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Publishes the authenticated identity to Postgres so Row Level Security applies.
 *
 * Laravel connects with the `postgres` role, which bypasses RLS. Inside a
 * request-scoped transaction we switch to the `authenticated` / `anon` role and
 * set request.jwt.claims, exactly as PostgREST does — so every policy using
 * private.current_business_id() keeps tenants isolated.
 */
class SetSupabaseContext
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('supabase.enforce_rls')) {
            return $next($request);
        }

        $user = $request->user();

        $role = $user ? 'authenticated' : 'anon';
        $claims = $user
            ? [
                'sub' => (string) $user->getAuthIdentifier(),
                'role' => 'authenticated',
                'business_id' => isset($user->business_id) ? (int) $user->business_id : null,
                'app_role' => $user->role ?? null,
            ]
            : ['role' => 'anon'];

        try {
            return DB::transaction(function () use ($role, $claims, $request, $next) {
                // SET LOCAL is transaction-scoped: it never leaks to the next request.
                DB::statement('set local role '.($role === 'authenticated' ? 'authenticated' : 'anon'));
                DB::statement("select set_config('request.jwt.claims', ?, true)", [json_encode($claims)]);
                DB::statement("select set_config('request.jwt.claim.sub', ?, true)", [(string) ($claims['sub'] ?? '')]);
                DB::statement("select set_config('request.jwt.claim.role', ?, true)", [$role]);

                return $next($request);
            });
        } catch (Throwable $e) {
            report($e);

            throw $e;
        }
    }
}
