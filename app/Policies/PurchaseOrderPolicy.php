<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, PurchaseOrder $order): bool
    {
        return $user->business_id === $order->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, PurchaseOrder $order): bool
    {
        return $user->business_id === $order->business_id &&
               in_array($order->status, ['draft', 'pending']);
    }

    public function delete(User $user, PurchaseOrder $order): bool
    {
        return $user->business_id === $order->business_id &&
               $order->status === 'draft';
    }

    public function approve(User $user, PurchaseOrder $order): bool
    {
        return $user->business_id === $order->business_id &&
               $order->status === 'pending';
    }
}