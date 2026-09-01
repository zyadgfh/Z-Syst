<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users-read');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('users-read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users-create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('users-update');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('users-delete');
    }
}
