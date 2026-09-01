<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UnitPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->business_id === $unit->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->business_id === $unit->business_id;
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->business_id === $unit->business_id;
    }
}