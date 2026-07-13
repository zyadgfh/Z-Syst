<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $companyId = null;

        if (app()->bound('tenant.company_id')) {
            $companyId = app('tenant.company_id');
        }

        if ($companyId) {
            $builder->where($model->getTable().'.company_id', $companyId);
        }
    }

    /**
     * Remove the tenant scope from the query.
     * This allows queries to access all tenants when needed.
     */
    public function remove(Builder $builder, Model $model)
    {
        $column = $model->getTable().'.company_id';

        // Get the current query's wheres and remove tenant-scope related ones
        $wheres = $builder->getQuery()->wheres;

        $builder->getQuery()->wheres = array_values(
            array_filter($wheres, function ($where) use ($column) {
                // Remove where clauses that reference the company_id column
                if (isset($where['column']) && $where['column'] === $column) {
                    // Only remove if it matches a simple equality where (our tenant scope)
                    if (isset($where['type']) && $where['type'] === 'Basic') {
                        return false;
                    }
                }
                return true;
            })
        );
    }
}
