<?php

namespace App\Policies;

use App\Models\Income;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncomePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Income $income): bool
    {
        return $user->business_id === $income->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Income $income): bool
    {
        return $user->business_id === $income->business_id;
    }

    public function delete(User $user, Income $income): bool
    {
        return $user->business_id === $income->business_id;
    }
}