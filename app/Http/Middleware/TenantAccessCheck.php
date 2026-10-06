<?php

namespace App\Http\Middleware;

use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantAccessCheck
{
    public function __construct(
        protected TenantResolver $resolver
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): mixed  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip check for super admin
        if ($user && $user->role === 'superadmin') {
            return $next($request);
        }

        // Check if user is trying to access another tenant's data
        if ($request->has('business_id') && $user) {
            $requestedTenantId = (int) $request->input('business_id');
            if (! $this->resolver->canAccessTenant($requestedTenantId)) {
                abort(403, 'You do not have permission to access this tenant data.');
            }
        }

        // Route model binding happens before route middleware. Enforce the tenant
        // boundary for every bound model that exposes a business_id, including
        // resources whose controller does not repeat the check.
        if ($user && $user->business_id) {
            foreach ($request->route()->parameters() as $value) {
                if (is_object($value) && isset($value->business_id)
                    && (int) $value->business_id !== (int) $user->business_id) {
                    abort(404);
                }
            }
        }

        return $next($request);
    }
}
