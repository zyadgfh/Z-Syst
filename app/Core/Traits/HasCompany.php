<?php

namespace App\Core\Traits;

use App\Scopes\TenantScope;
use Illuminate\Support\Facades\Schema;

trait HasCompany
{
    public static function bootHasCompany()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            $column = $model->getCompanyColumn();

            if (empty($model->{$column}) && app()->bound('tenant.company_id')) {
                $model->{$column} = app('tenant.company_id');
            }
        });

        static::updating(function ($model) {
            $column = $model->getCompanyColumn();

            // Prevent tenant switching
            if ($model->isDirty($column) && $model->getOriginal($column) !== $model->{$column}) {
                throw new \Exception('Cannot change company_id - tenant switching is not allowed');
            }
        });
    }

    protected function getCompanyColumn(): string
    {
        $table = $this->getTable();

        if (Schema::hasColumn($table, 'company_id')) {
            return 'company_id';
        }

        if (Schema::hasColumn($table, 'business_id')) {
            return 'business_id';
        }

        return 'company_id';
    }

    /**
     * Remove tenant scope for queries that need all tenants.
     */
    public function scopeWithAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope a query to a specific company id.
     */
    public function scopeForCompany($query, $companyId)
    {
        $column = (new static)->getCompanyColumn();

        return $query->withoutGlobalScope(TenantScope::class)->where($column, $companyId);
    }
}
