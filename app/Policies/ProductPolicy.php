<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Product $product): bool
    {
        return $user->business_id === $product->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->business_id === $product->business_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->business_id === $product->business_id;
    }

    public function updateStock(User $user, Product $product): bool
    {
        return $user->business_id === $product->business_id;
    }

    public function managePrices(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function manageBarcodes(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function manageCategories(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function viewCost(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function viewProfit(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function import(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function export(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function bulkUpdate(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function archive(User $user, Product $product): bool
    {
        return $user->business_id === $product->business_id;
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->business_id === $product->business_id && $product->trashed();
    }
}
