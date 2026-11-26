<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantResolver
{
    /**
     * Resolve the tenant identity for this request.
     *
     * Tenant identity is derived ONLY from a verified principal (the
     * authenticated user's own business_id). Client-supplied parameters
     * (business_id, tenant_id, …) are NEVER trusted as a source of tenant
     * identity — see TenantAccessCheck for the single, audited place where a
     * requested business_id is compared against the verified principal.
     */
    public function resolve(Request $request): ?int
    {
        $user = $request->user();

        // Super admin should not have tenant context for admin routes
        if ($user && ($user->role ?? null) === 'superadmin' && $request->is('admin/*')) {
            return null;
        }

        // Regular users get their business_id from their verified identity.
        if ($user && isset($user->business_id) && $user->business_id !== null) {
            return (int) $user->business_id;
        }

        // No verified principal (or principal without a tenant) → no context.
        // Client input is deliberately NOT consulted here.
        return null;
    }

    /**
     * Resolve the business id a controller should filter by: the caller's own
     * tenant, never a client-supplied one — with one reviewed administrative
     * exception: a superadmin may scope explicitly via business_id.
     */
    public function trustedBusinessId(Request $request): ?int
    {
        $user = $request->user() ?? Auth::user();

        if (!$user) {
            return null;
        }

        $own = (isset($user->business_id) && $user->business_id !== null)
            ? (int) $user->business_id
            : null;

        // Reviewed administrative exception: superadmin may pass an explicit
        // business_id to scope platform-wide queries; without one they keep
        // their own (typically null) context.
        if (($user->role ?? null) === 'superadmin') {
            return $request->filled('business_id')
                ? (int) $request->input('business_id')
                : $own;
        }

        // Identity first, client input ignored. A non-superadmin without a
        // tenant has no business being scoped here: returning null would make
        // callers fall through to unfiltered (platform-wide) queries, because
        // TenantScoped skips itself when business_id is empty — deny instead,
        // mirroring EnsureBusinessContext.
        if ($own === null) {
            abort(403, 'Business context is required.');
        }

        return $own;
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
     * Check if current user can access specified tenant.
     *
     * Deny-by-default: without an authenticated principal there is no tenant
     * access. (A previous fallback trusted the most recently provisioned
     * user's tenant, which would grant unauthenticated callers access to that
     * tenant; it is gone.)
     */
    public function canAccessTenant(int $businessId): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super admin can access all tenants
        if (($user->role ?? null) === 'superadmin') {
            return true;
        }

        // Regular users can only access their own tenant
        return isset($user->business_id)
            && $user->business_id !== null
            && (int) $user->business_id === (int) $businessId;
    }
}
