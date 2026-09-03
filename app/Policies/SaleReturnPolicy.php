<?php

namespace App\Policies;

use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SaleReturnPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, SaleReturn $return): bool
    {
        return $user->business_id === $return->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, SaleReturn $return): bool
    {
        return $user->business_id === $return->business_id;
    }

    public function delete(User $user, SaleReturn $return): bool
    {
        return $user->business_id === $return->business_id;
    }
}