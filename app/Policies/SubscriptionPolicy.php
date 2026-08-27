<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->business_id === $subscription->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->business_id === $subscription->business_id;
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->business_id === $subscription->business_id;
    }

    public function manage(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function renew(User $user): bool
    {
        return $user->business_id !== null;
    }
}