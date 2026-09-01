<?php

namespace App\Policies;

use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WarehousePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->business_id === $warehouse->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->business_id === $warehouse->business_id;
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->business_id === $warehouse->business_id;
    }
}