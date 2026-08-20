<?php

namespace App\Policies;

use App\Models\InsurancePolicy;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InsurancePolicyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, InsurancePolicy $policy): bool
    {
        return $user->business_id === $policy->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, InsurancePolicy $policy): bool
    {
        return $user->business_id === $policy->business_id;
    }

    public function delete(User $user, InsurancePolicy $policy): bool
    {
        return $user->business_id === $policy->business_id;
    }
}