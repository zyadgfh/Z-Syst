<?php

namespace App\Policies;

use App\Models\BusinessCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BusinessCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('business-categories-read');
    }

    public function view(User $user, BusinessCategory $category): bool
    {
        return $user->hasPermissionTo('business-categories-read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('business-categories-read');
    }

    public function update(User $user, BusinessCategory $category): bool
    {
        return $user->hasPermissionTo('business-categories-update');
    }

    public function delete(User $user, BusinessCategory $category): bool
    {
        return $user->hasPermissionTo('business-categories-delete');
    }
}
