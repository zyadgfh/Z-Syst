<?php

namespace App\Policies;

use App\Models\MedicineType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MedicineTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, MedicineType $type): bool
    {
        return $user->business_id === $type->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, MedicineType $type): bool
    {
        return $user->business_id === $type->business_id;
    }

    public function delete(User $user, MedicineType $type): bool
    {
        return $user->business_id === $type->business_id;
    }
}