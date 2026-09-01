<?php

namespace App\Policies;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CurrencyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('currencies-read');
    }

    public function view(User $user, Currency $currency): bool
    {
        return $user->hasPermissionTo('currencies-read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('currencies-create');
    }

    public function update(User $user, Currency $currency): bool
    {
        return $user->hasPermissionTo('currencies-update');
    }

    public function delete(User $user, Currency $currency): bool
    {
        return $user->hasPermissionTo('currencies-delete');
    }
}
