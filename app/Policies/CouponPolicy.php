<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CouponPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('coupons-read');
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->hasPermissionTo('coupons-read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('coupons-create');
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->hasPermissionTo('coupons-update');
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->hasPermissionTo('coupons-delete');
    }
}
