<?php

namespace App\Shared\Middleware;

use App\Models\Company;
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
            // Validate tenant access
            if ($request->user() && ! $this->canAccessTenant($request->user(), $company)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this tenant.',
                ], 403);
            }

            $tenantManager->bindTenant($company);

            return $next($request);
        }

        // Allow public endpoints to proceed without tenant
        if (! $request->user() && ! $request->header('X-Company-Id') && ! $request->route('company')) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tenant not found or user is not assigned to a company.',
        ], 404);
    }

    protected function canAccessTenant($user, Company $company): bool
    {
        // Super-admins can access any tenant
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Regular users can only access their own tenant
        return $user->company_id === $company->id;
    }
}
