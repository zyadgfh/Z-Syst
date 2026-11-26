<?php

namespace App\Http\Middleware;

use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Establishes the tenant context for a request.
 *
 * Registered to run AFTER an identity can be resolved (session started in the
 * `web` group; `tenant.context` runs right after `auth:sanctum` on API routes)
 * — never as blind global middleware before authentication. The value always
 * comes from TenantResolver::resolve(), which derives it from the verified
 * principal only; client input is never merged into the request here.
 */
class TenantContextMiddleware
{
    public function __construct(protected TenantResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolver->resolve($request);

        if ($tenantId !== null) {
            $request->attributes->set('tenant_id', $tenantId);
            app()->instance('tenant_id', $tenantId);
            config(['app.current_business_id' => $tenantId]);
        }

        return $next($request);
    }
}
