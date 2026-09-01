<?php

namespace App\Policies;

use App\Models\PurchaseReturn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseReturnPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, PurchaseReturn $return): bool
    {
        return $user->business_id === $return->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, PurchaseReturn $return): bool
    {
        return $user->business_id === $return->business_id;
    }

    public function delete(User $user, PurchaseReturn $return): bool
    {
        return $user->business_id === $return->business_id;
    }
}