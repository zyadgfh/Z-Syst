<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenantManager = app(TenantManager::class);

        $company = $tenantManager->resolveFromRequest($request);

        if ($company) {
            $tenantManager->bindTenant($company);

            return $next($request);
        }

        if ($request->user() || $request->header('X-Company-Id') || $request->route('company')) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found or user is not assigned to a company.',
            ], 404);
        }

        return $next($request);
    }
}
