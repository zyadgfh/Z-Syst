<?php

namespace App\Policies;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReceiptPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Receipt $receipt): bool
    {
        return $user->business_id === $receipt->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function generate(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Receipt $receipt): bool
    {
        return $user->business_id === $receipt->business_id;
    }

    public function delete(User $user, Receipt $receipt): bool
    {
        return $user->business_id === $receipt->business_id;
    }
}