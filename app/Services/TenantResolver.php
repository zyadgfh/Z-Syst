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

        // Tenant context must never be selected from an unauthenticated
        // request. Authentication middleware remains authoritative.
        if (!$user) {
            return null;
        }

        // Superadmins may explicitly select a tenant for API/admin work.
        if ($user->role === 'superadmin') {
            if ($request->has('business_id')) {
                return (int) $request->input('business_id');
            }

            // Superadmin admin routes intentionally operate without a tenant.
            if ($request->is('admin/*')) {
                return null;
            }

            return null;
        }

        // Regular users are permanently bound to their own business.
        if (isset($user->business_id)) {
            return (int) $user->business_id;
        }

        return null;
    }

    public function getCurrentTenant(): ?Business
    {
        $tenantId = app()->bound('tenant_id') ? app('tenant_id') : null;

        return $tenantId ? Business::find($tenantId) : null;
    }

    public function canAccessTenant(int $businessId): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->role === 'superadmin') {
            return true;
        }

        return (int) $user->business_id === $businessId;
    }
}
