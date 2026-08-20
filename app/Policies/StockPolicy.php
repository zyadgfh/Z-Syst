<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Stock $stock): bool
    {
        return $user->business_id === $stock->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Stock $stock): bool
    {
        return $user->business_id === $stock->business_id;
    }

    public function delete(User $user, Stock $stock): bool
    {
        return $user->business_id === $stock->business_id;
    }
}