<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->business_id === $sale->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->business_id === $sale->business_id;
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->business_id === $sale->business_id;
    }
}