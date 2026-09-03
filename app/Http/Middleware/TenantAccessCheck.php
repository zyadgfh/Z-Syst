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

        // Check route parameters for tenant IDs
        $routeParameters = $request->route()->parameters();
        foreach ($routeParameters as $key => $value) {
            if (str_ends_with($key, '_id') || str_ends_with($key, 'Id')) {
                // This is a basic check - specific models should have their own policies
                // Add more sophisticated checking as needed
            }
        }

        return $next($request);
    }
}
