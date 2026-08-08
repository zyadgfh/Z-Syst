<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantResolver
{
    public function resolve(Request $request): ?int
    {
        $user = $request->user();

        // Super admin should not have tenant context for admin routes
        if ($user && $user->role === 'superadmin' && $request->is('admin/*')) {
            return null;
        }

        // Regular users get their business_id
        if ($user && isset($user->business_id)) {
            return (int) $user->business_id;
        }

        // For API requests, allow business_id parameter
        if ($request->has('business_id')) {
            return (int) $request->input('business_id');
        }

        return null;
    }

    /**
     * Get current tenant model
     *
     * @return Business|null
     */
    public function getCurrentTenant(): ?Business
    {
        $tenantId = app('tenant_id');
        return $tenantId ? Business::find($tenantId) : null;
    }

    /**
     * Check if current user can access specified tenant
     *
     * @param int $businessId
     * @return bool
     */
    public function canAccessTenant(int $businessId): bool
    {
        $user = Auth::user();
        
        // Super admin can access all tenants
        if ($user && $user->role === 'superadmin') {
            return true;
        }

        // Regular users can only access their own tenant
        return $user && $user->business_id === $businessId;
    }
}
