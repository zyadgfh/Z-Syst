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

    // optional remove - left intentionally simple
    public function remove(Builder $builder, Model $model)
    {
        // Not implemented: removal depends on query grammar internals
    }
}
