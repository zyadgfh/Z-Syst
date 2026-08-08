<?php

namespace App\Policies;

use App\Models\StockAudit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockAuditPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any stock audits.
     */
    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    /**
     * Determine whether the user can view the stock audit.
     */
    public function view(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id;
    }

    /**
     * Determine whether the user can create stock audits.
     */
    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    /**
     * Determine whether the user can update the stock audit.
     */
    public function update(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               $audit->status !== 'completed';
    }

    /**
     * Determine whether the user can delete the stock audit.
     */
    public function delete(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               $audit->status === 'pending';
    }

    /**
     * Determine whether the user can start the stock audit.
     */
    public function start(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               $audit->status === 'pending';
    }

    /**
     * Determine whether the user can complete the stock audit.
     */
    public function complete(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               $audit->status === 'in_progress';
    }

    /**
     * Determine whether the user can cancel the stock audit.
     */
    public function cancel(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               in_array($audit->status, ['pending', 'in_progress']);
    }

    /**
     * Determine whether the user can add details to the stock audit.
     */
    public function addDetails(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               in_array($audit->status, ['pending', 'in_progress']);
    }

    /**
     * Determine whether the user can post reconciliations for the stock audit.
     */
    public function postReconciliations(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               $audit->status === 'completed';
    }
}
