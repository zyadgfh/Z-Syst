<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        // If addon not enabled, skip
        if (!moduleCheck('MultiBranchAddon')) return;

        $user = auth()->user();
        if (!$user) return;

        // Skip global scope for shop-owner when not inside a branch
        if ($user->role === 'shop-owner' && !$user->active_branch_id) {
            return;
        }

        if ($user->active_branch_id) {
            $builder->where($model->getTable() . '.branch_id', $user->active_branch_id);
            return;
        }

        // Otherwise fall back to user's fixed branch (for staff)
        if (!is_null($user->branch_id)) {
            $builder->where($model->getTable() . '.branch_id', $user->branch_id);
        }
    }
}
