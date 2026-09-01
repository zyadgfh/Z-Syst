<?php

namespace App\Policies;

use App\Models\InsuranceClaim;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InsuranceClaimPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, InsuranceClaim $claim): bool
    {
        return $user->business_id === $claim->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, InsuranceClaim $claim): bool
    {
        return $user->business_id === $claim->business_id;
    }

    public function delete(User $user, InsuranceClaim $claim): bool
    {
        return $user->business_id === $claim->business_id;
    }
}