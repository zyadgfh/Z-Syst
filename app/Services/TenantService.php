<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Auth;

class TenantService
{
    /**
     * Get current tenant
     *
     * @return Business|null
     */
    public function getCurrentTenant(): ?Business
    {
        return app('current_tenant');
    }

    /**
     * Get current tenant ID
     *
     * @return int|null
     */
    public function getCurrentTenantId(): ?int
    {
        $tenant = $this->getCurrentTenant();
        return $tenant ? $tenant->id : null;
    }

    /**
     * Check if user belongs to tenant
     *
     * @param int $businessId
     * @return bool
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
     *
     * @param int $businessId
     * @return void
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
     *
     * @return void
     */
    public function clearTenantContext(): void
    {
        app()->forgetInstance('current_tenant');
    }
}
