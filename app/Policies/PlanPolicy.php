<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('plans-read');
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->hasPermissionTo('plans-read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('plans-create');
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->hasPermissionTo('plans-update');
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->hasPermissionTo('plans-delete');
    }
}
