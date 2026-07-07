<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TenantManager;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenantManager = app(TenantManager::class);

        $company = $tenantManager->resolveFromRequest($request);

        if ($company) {
            $tenantManager->bindTenant($company);
        }

        return $next($request);
    }
}
