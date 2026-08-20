<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockMovementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $user->business_id === $stockMovement->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, StockMovement $stockMovement): bool
    {
        // Stock movements should generally not be updated after creation
        // They are audit records
        return false;
    }

    public function delete(User $user, StockMovement $stockMovement): bool
    {
        // Stock movements should not be deleted - they are audit records
        return false;
    }

    public function restore(User $user, StockMovement $stockMovement): bool
    {
        return $user->business_id === $stockMovement->business_id && in_array($user->role, ['admin', 'superadmin']);
    }

    public function forceDelete(User $user, StockMovement $stockMovement): bool
    {
        return false;
    }
}
