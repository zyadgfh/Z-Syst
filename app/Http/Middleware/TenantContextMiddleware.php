<?php

namespace App\Http\Middleware;

use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantContextMiddleware
{
    public function __construct(protected TenantResolver $resolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolver->resolve($request);

        if ($tenantId !== null) {
            $request->attributes->set('tenant_id', $tenantId);
            $request->merge(['tenant_id' => $tenantId]);
            app()->instance('tenant_id', $tenantId);
            config(['app.current_business_id' => $tenantId]);
        }

        return $next($request);
    }
}
