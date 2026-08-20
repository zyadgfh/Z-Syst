<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->business_id === $supplier->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->business_id === $supplier->business_id;
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->business_id === $supplier->business_id;
    }
}