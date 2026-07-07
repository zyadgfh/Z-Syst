<?php

namespace App\Policies;

use App\Models\User;
use App\Models\User as Model;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasPermission('users.edit');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermission('users.delete');
    }
}
