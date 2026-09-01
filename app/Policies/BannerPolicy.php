<?php

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BannerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('banners-read');
    }

    public function view(User $user, Banner $banner): bool
    {
        return $user->hasPermissionTo('banners-read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('banners-create');
    }

    public function update(User $user, Banner $banner): bool
    {
        return $user->hasPermissionTo('banners-update');
    }

    public function delete(User $user, Banner $banner): bool
    {
        return $user->hasPermissionTo('banners-delete');
    }
}
