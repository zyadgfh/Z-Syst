<?php

namespace App\Policies;

use App\Models\LoyaltyProgram;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LoyaltyProgramPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, LoyaltyProgram $program): bool
    {
        return $user->business_id === $program->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, LoyaltyProgram $program): bool
    {
        return $user->business_id === $program->business_id;
    }

    public function delete(User $user, LoyaltyProgram $program): bool
    {
        return $user->business_id === $program->business_id;
    }
}