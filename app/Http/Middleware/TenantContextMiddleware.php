<?php

namespace App\Http\Middleware;

use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantContextMiddleware
{
    public function __construct(protected TenantResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        // This middleware is global, so the route's auth:sanctum middleware
        // has not necessarily executed yet. Resolve the Sanctum user directly
        // for API requests instead of relying only on the default web guard.
        if ($request->user() === null && $request->is('api/*')) {
            $request->setUserResolver(fn () => $request->user('sanctum'));
        }

        $tenantId = $this->resolver->resolve($request);

        $contextSet = $tenantId !== null;

        if ($contextSet) {
            $request->attributes->set('tenant_id', $tenantId);
            $request->merge(['tenant_id' => $tenantId]);
            app()->instance('tenant_id', $tenantId);
            config(['app.current_business_id' => $tenantId]);
        }

        try {
            return $next($request);
        } finally {
            if ($contextSet) {
                app()->forgetInstance('tenant_id');
                config(['app.current_business_id' => null]);
            }
        }
    }
}
