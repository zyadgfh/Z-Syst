<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BusinessPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('business-read');
    }

    public function view(User $user, Business $business): bool
    {
        return $user->hasPermissionTo('business-read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('business-create');
    }

    public function update(User $user, Business $business): bool
    {
        return $user->hasPermissionTo('business-update');
    }

    public function delete(User $user, Business $business): bool
    {
        return $user->hasPermissionTo('business-delete');
    }
}
