<?php

namespace App\Policies;

use App\Models\BatchLot;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BatchLotPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, BatchLot $batchLot): bool
    {
        return $user->business_id === $batchLot->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, BatchLot $batchLot): bool
    {
        return $user->business_id === $batchLot->business_id;
    }

    public function delete(User $user, BatchLot $batchLot): bool
    {
        return $user->business_id === $batchLot->business_id && in_array($user->role, ['admin', 'superadmin']);
    }

    public function recall(User $user, BatchLot $batchLot): bool
    {
        return $user->business_id === $batchLot->business_id && in_array($user->role, ['admin', 'superadmin']);
    }
}
