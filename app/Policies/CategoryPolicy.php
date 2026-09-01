<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Category $category): bool
    {
        return $user->business_id === $category->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Category $category): bool
    {
        return $user->business_id === $category->business_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->business_id === $category->business_id;
    }
}