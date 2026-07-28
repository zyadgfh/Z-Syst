<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FinancialAuditLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinancialAuditPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any financial audits.
     */
    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    /**
     * Determine whether the user can view the financial audit.
     */
    public function view(User $user, FinancialAuditLog $audit): bool
    {
        return $user->business_id === $audit->business_id;
    }

    /**
     * Determine whether the user can create financial audits.
     */
    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    /**
     * Determine whether the user can update the financial audit.
     */
    public function update(User $user, FinancialAuditLog $audit): bool
    {
        return $user->business_id === $audit->business_id && 
               $audit->status !== 'completed';
    }

    /**
     * Determine whether the user can delete the financial audit.
     */
    public function delete(User $user, FinancialAuditLog $audit): bool
    {
        return $user->business_id === $audit->business_id && 
               $audit->status === 'pending';
    }

    /**
     * Determine whether the user can start the financial audit.
     */
    public function start(User $user, FinancialAuditLog $audit): bool
    {
        return $user->business_id === $audit->business_id && 
               $audit->status === 'pending';
    }

    /**
     * Determine whether the user can execute the financial audit.
     */
    public function execute(User $user, FinancialAuditLog $audit): bool
    {
        return $user->business_id === $audit->business_id && 
               $audit->status === 'in_progress';
    }

    /**
     * Determine whether the user can complete the financial audit.
     */
    public function complete(User $user, FinancialAuditLog $audit): bool
    {
        return $user->business_id === $audit->business_id && 
               $audit->status === 'in_progress';
    }

    /**
     * Determine whether the user can cancel the financial audit.
     */
    public function cancel(User $user, FinancialAuditLog $audit): bool
    {
        return $user->business_id === $audit->business_id && 
               in_array($audit->status, ['pending', 'in_progress']);
    }

    /**
     * Determine whether the user can view the financial audit report.
     */
    public function viewReport(User $user, FinancialAuditLog $audit): bool
    {
        return $user->business_id === $audit->business_id && 
               $audit->status === 'completed';
    }

    /**
     * Determine whether the user can view transaction details.
     */
    public function viewTransactions(User $user, FinancialAuditLog $audit): bool
    {
        return $user->business_id === $audit->business_id && 
               in_array($audit->status, ['in_progress', 'completed']);
    }
}