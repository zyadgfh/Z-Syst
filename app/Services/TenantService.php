<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Auth;

class TenantService
{
    /**
     * Get current tenant
     */
    public function getCurrentTenant(): ?Business
    {
        return app('current_tenant');
    }

    /**
     * Get current tenant ID
     */
    public function getCurrentTenantId(): ?int
    {
        $tenant = $this->getCurrentTenant();

        return $tenant ? $tenant->id : null;
    }

    /**
     * Check if user belongs to tenant
     */
    public function isTenantAccessible(int $businessId): bool
    {
        $user = Auth::user();

        // Super admin can access all tenants
        if ($user->role === 'superadmin') {
            return true;
        }

        // Regular users can only access their own tenant
        return $user->business_id === $businessId;
    }

    /**
     * Set tenant context manually (for jobs/commands)
     */
    public function setTenantContext(int $businessId): void
    {
        $tenant = Business::find($businessId);
        if ($tenant) {
            app()->instance('current_tenant', $tenant);
        }
    }

    /**
     * Clear tenant context
     */
    public function clearTenantContext(): void
    {
        app()->forgetInstance('current_tenant');
    }
}
