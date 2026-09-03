<?php

namespace App\Policies;

use App\Models\GRN;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GRNPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, GRN $grn): bool
    {
        return $user->business_id === $grn->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, GRN $grn): bool
    {
        return $user->business_id === $grn->business_id &&
               $grn->status === 'pending';
    }

    public function delete(User $user, GRN $grn): bool
    {
        return $user->business_id === $grn->business_id &&
               $grn->status === 'pending';
    }
}