<?php

namespace App\Policies;

use App\Models\Barcode;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BarcodePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('products-view');
    }

    public function view(User $user, Barcode $barcode): bool
    {
        return $user->hasPermissionTo('products-view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('products-create');
    }

    public function update(User $user, Barcode $barcode): bool
    {
        return $user->hasPermissionTo('products-edit');
    }

    public function delete(User $user, Barcode $barcode): bool
    {
        return $user->hasPermissionTo('products-delete');
    }

    public function print(User $user): bool
    {
        return $user->hasPermissionTo('products-view');
    }
}
