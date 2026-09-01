<?php

namespace App\Policies;

use App\Models\Manufacturer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManufacturerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Manufacturer $manufacturer): bool
    {
        return $user->business_id === $manufacturer->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Manufacturer $manufacturer): bool
    {
        return $user->business_id === $manufacturer->business_id;
    }

    public function delete(User $user, Manufacturer $manufacturer): bool
    {
        return $user->business_id === $manufacturer->business_id;
    }
}