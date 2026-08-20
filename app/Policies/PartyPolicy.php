<?php

namespace App\Policies;

use App\Models\Party;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PartyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Party $party): bool
    {
        return $user->business_id === $party->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Party $party): bool
    {
        return $user->business_id === $party->business_id;
    }

    public function delete(User $user, Party $party): bool
    {
        return $user->business_id === $party->business_id;
    }
}