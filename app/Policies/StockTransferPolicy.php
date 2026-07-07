<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * StockTransferPolicy
 * 
 * Authorization policy for stock transfer operations.
 * Defines permissions for viewing, creating, approving, shipping, receiving, and cancelling transfers.
 */
class StockTransferPolicy
{
    use HandlesAuthorization;

    /**
     * Global before hook to allow super admins to bypass policy checks.
     */
    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any stock transfers.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_stock_transfers') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the stock transfer.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return bool
     */
    public function view(User $user, StockTransfer $stockTransfer): bool
    {
        // User must belong to the same company
        if ($stockTransfer->company_id !== $user->company_id) {
            return false;
        }

        // User must have permission or be super admin
        return $user->hasPermission('view_stock_transfers') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create stock transfers.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_stock_transfers') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the stock transfer.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return bool
     */
    public function update(User $user, StockTransfer $stockTransfer): bool
    {
        // User must belong to the same company
        if ($stockTransfer->company_id !== $user->company_id) {
            return false;
        }

        // Only allow updates for pending transfers
        if (!$stockTransfer->canBeCancelled()) {
            return false;
        }

        return $user->hasPermission('update_stock_transfers') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the stock transfer.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return bool
     */
    public function delete(User $user, StockTransfer $stockTransfer): bool
    {
        // Soft deletes only, and only for cancelled transfers
        if ($stockTransfer->status !== 'cancelled') {
            return false;
        }

        // User must belong to the same company
        if ($stockTransfer->company_id !== $user->company_id) {
            return false;
        }

        return $user->hasPermission('delete_stock_transfers') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can approve the stock transfer.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return bool
     */
    public function approve(User $user, StockTransfer $stockTransfer): bool
    {
        // User must belong to the same company
        if ($stockTransfer->company_id !== $user->company_id) {
            return false;
        }

        // Transfer must be in approvable state
        if (!$stockTransfer->canBeApproved()) {
            return false;
        }

        // User must have permission or be super admin
        return $user->hasPermission('approve_stock_transfers') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can reject the stock transfer.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return bool
     */
    public function reject(User $user, StockTransfer $stockTransfer): bool
    {
        // User must belong to the same company
        if ($stockTransfer->company_id !== $user->company_id) {
            return false;
        }

        // Transfer must be in rejectable state
        if (!$stockTransfer->canBeRejected()) {
            return false;
        }

        // User must have permission or be super admin
        return $user->hasPermission('reject_stock_transfers') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can ship the stock transfer.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return bool
     */
    public function ship(User $user, StockTransfer $stockTransfer): bool
    {
        // User must belong to the same company
        if ($stockTransfer->company_id !== $user->company_id) {
            return false;
        }

        // Transfer must be in shippable state
        if (!$stockTransfer->canBeShipped()) {
            return false;
        }

        // User must belong to the source branch or be super admin
        if ($user->branch_id !== $stockTransfer->from_branch_id && !$user->isSuperAdmin()) {
            return false;
        }

        // User must have permission or be super admin
        return $user->hasPermission('ship_stock_transfers') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can receive the stock transfer.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return bool
     */
    public function receive(User $user, StockTransfer $stockTransfer): bool
    {
        // User must belong to the same company
        if ($stockTransfer->company_id !== $user->company_id) {
            return false;
        }

        // Transfer must be in receivable state
        if (!$stockTransfer->canBeReceived()) {
            return false;
        }

        // User must belong to the destination branch or be super admin
        if ($user->branch_id !== $stockTransfer->to_branch_id && !$user->isSuperAdmin()) {
            return false;
        }

        // User must have permission or be super admin
        return $user->hasPermission('receive_stock_transfers') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can cancel the stock transfer.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return bool
     */
    public function cancel(User $user, StockTransfer $stockTransfer): bool
    {
        // User must belong to the same company
        if ($stockTransfer->company_id !== $user->company_id) {
            return false;
        }

        // Transfer must be in cancellable state
        if (!$stockTransfer->canBeCancelled()) {
            return false;
        }

        // User must belong to either source or destination branch or be super admin
        if ($user->branch_id !== $stockTransfer->from_branch_id 
            && $user->branch_id !== $stockTransfer->to_branch_id 
            && !$user->isSuperAdmin()) {
            return false;
        }

        // User must have permission or be super admin
        return $user->hasPermission('cancel_stock_transfers') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view transfer statistics.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewStatistics(User $user): bool
    {
        return $user->hasPermission('view_stock_transfer_statistics') || $user->isSuperAdmin();
    }
}
