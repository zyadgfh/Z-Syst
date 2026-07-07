<?php

namespace App\Traits;

use App\Scopes\TenantScope;

trait HasCompany
{
    public static function bootHasCompany()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->company_id) && app()->bound('tenant.company_id')) {
                $model->company_id = app('tenant.company_id');
            }
        });
    }

    /**
     * Remove tenant scope for queries that need all tenants.
     */
    public function scopeWithAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
