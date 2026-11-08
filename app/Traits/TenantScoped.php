<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Global tenant isolation for business-owned models.
 *
 * Every authenticated, business-bound user is automatically restricted to
 * rows of their own business — for reads (all queries) and writes (creating).
 *
 * The scope is skipped when:
 *  - there is no authenticated user (console jobs, seeders, queue workers);
 *  - the user is a superadmin (admin panel manages all businesses);
 *  - the user has no business_id yet (onboarding).
 *
 * Apply by "use TenantScoped;" on the model. Models that must always be
 * cross-tenant should NOT use this trait.
 */
trait TenantScoped
{
    /**
     * Re-entrancy guard for the auth principal lookup below. Session auth
     * resolves the current user via User::find() — which runs this scope —
     * and the scope calls auth()->user() again. Without the guard that is
     * infinite recursion (OOM); with it, the innermost fetch runs unscoped,
     * which is correct: the auth principal itself must not be tenant-scoped.
     */
    protected static bool $tenantScopeAuthBusy = false;

    public static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (static::$tenantScopeAuthBusy) {
                return;
            }

            static::$tenantScopeAuthBusy = true;
            try {
                $user = \Illuminate\Support\Facades\Auth::user();
            } finally {
                static::$tenantScopeAuthBusy = false;
            }

            if (!$user || ($user->role ?? null) === 'superadmin' || !$user->business_id) {
                return;
            }

            $builder->where($builder->getModel()->getTable().'.business_id', $user->business_id);
        });

        static::creating(function ($model) {
            $user = \Illuminate\Support\Facades\Auth::user();
            if (!$model->business_id && $user && $user->business_id) {
                $model->business_id = $user->business_id;
            }
        });
    }
}
