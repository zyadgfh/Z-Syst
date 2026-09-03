<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockTransferPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('inventory-view');
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        return $user->hasPermissionTo('inventory-view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('inventory-create');
    }

    public function update(User $user, StockTransfer $transfer): bool
    {
        return $user->hasPermissionTo('inventory-edit');
    }

    public function delete(User $user, StockTransfer $transfer): bool
    {
        return $user->hasPermissionTo('inventory-delete');
    }

    public function complete(User $user, StockTransfer $transfer): bool
    {
        return $user->hasPermissionTo('inventory-edit');
    }

    public function cancel(User $user, StockTransfer $transfer): bool
    {
        return $user->hasPermissionTo('inventory-edit');
    }
}
