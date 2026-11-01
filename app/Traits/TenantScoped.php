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
    public static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = auth()->user();

            if (!$user || $user->role === 'superadmin' || !$user->business_id) {
                return;
            }

            $builder->where($builder->getModel()->getTable().'.business_id', $user->business_id);
        });

        static::creating(function ($model) {
            if (!$model->business_id && ($user = auth()->user()) && $user->business_id) {
                $model->business_id = $user->business_id;
            }
        });
    }
}
