<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrescriptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Prescription $prescription): bool
    {
        return $user->business_id === $prescription->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Prescription $prescription): bool
    {
        return $user->business_id === $prescription->business_id;
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->business_id === $prescription->business_id;
    }
}