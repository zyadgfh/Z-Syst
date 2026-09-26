<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Services\TenantResolver;
use Symfony\Component\HttpFoundation\Response;

class TenantAccessCheck
{
    public function __construct(
        protected TenantResolver $resolver
    ) {}

    /**
     * Enforce tenant isolation for explicit tenant identifiers and
     * route-bound Eloquent models.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Superadmins are the only users allowed to cross tenant boundaries.
        if ($user->role === 'superadmin') {
            return $next($request);
        }

        if (empty($user->business_id)) {
            abort(403, 'Tenant context is required.');
        }

        $requestedTenantId = $request->input('business_id');

        if ($requestedTenantId !== null && !$this->resolver->canAccessTenant((int) $requestedTenantId)) {
            abort(403, 'You do not have permission to access this tenant data.');
        }

        // SubstituteBindings has already resolved route model bindings for
        // routes using this middleware. Reject any bound tenant-owned model
        // belonging to another business.
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if (!$parameter instanceof Model) {
                continue;
            }

            $modelTenantId = $parameter->getAttribute('business_id');

            if ($modelTenantId !== null && (int) $modelTenantId !== (int) $user->business_id) {
                abort(403, 'You do not have permission to access this tenant data.');
            }
        }

        return $next($request);
    }
}
