<?php

namespace App\Policies;

use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierInvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('purchases-view');
    }

    public function view(User $user, SupplierInvoice $invoice): bool
    {
        return $user->hasPermissionTo('purchases-view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('purchases-create');
    }

    public function update(User $user, SupplierInvoice $invoice): bool
    {
        return $user->hasPermissionTo('purchases-edit');
    }

    public function delete(User $user, SupplierInvoice $invoice): bool
    {
        return $user->hasPermissionTo('purchases-delete');
    }
}
