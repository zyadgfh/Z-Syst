<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $companyId = null;

        if (app()->bound('tenant.company_id')) {
            $companyId = app('tenant.company_id');
        }

        $column = $this->getTenantColumn($model);

        if ($companyId && $column) {
            $builder->where($model->getTable().'.'.$column, $companyId);
        }
    }

    public function applyForUser(Builder $builder, Model $model, $user)
    {
        // Super-admins can see all tenants
        if ($user && $user->isSuperAdmin()) {
            return $builder;
        }

        // Regular users only see their tenant
        return $this->apply($builder, $model);
    }

    /**
     * Remove the tenant scope from the query.
     * This allows queries to access all tenants when needed.
     */
    public function remove(Builder $builder, Model $model)
    {
        $column = $this->getTenantColumn($model);
        $qualifiedColumn = $column ? $model->getTable().'.'.$column : null;

        if (! $qualifiedColumn) {
            return;
        }

        // Get the current query's wheres and remove tenant-scope related ones
        $wheres = $builder->getQuery()->wheres;

        $builder->getQuery()->wheres = array_values(
            array_filter($wheres, function ($where) use ($qualifiedColumn) {
                // Remove where clauses that reference the tenant column
                if (isset($where['column']) && $where['column'] === $qualifiedColumn) {
                    // Only remove if it matches a simple equality where (our tenant scope)
                    if (isset($where['type']) && $where['type'] === 'Basic') {
                        return false;
                    }
                }
                return true;
            })
        );
    }

    protected function getTenantColumn(Model $model): ?string
    {
        $table = $model->getTable();

        if (Schema::hasColumn($table, 'company_id')) {
            return 'company_id';
        }

        if (Schema::hasColumn($table, 'business_id')) {
            return 'business_id';
        }

        return null;
    }
}
