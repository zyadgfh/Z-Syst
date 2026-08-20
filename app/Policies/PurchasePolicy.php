<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchasePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->business_id === $purchase->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $user->business_id === $purchase->business_id;
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $user->business_id === $purchase->business_id;
    }
}