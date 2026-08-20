<?php

namespace App\Policies;

use App\Models\Tax;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaxPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Tax $tax): bool
    {
        return $user->business_id === $tax->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Tax $tax): bool
    {
        return $user->business_id === $tax->business_id;
    }

    public function delete(User $user, Tax $tax): bool
    {
        return $user->business_id === $tax->business_id;
    }
}