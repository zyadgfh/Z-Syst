<?php

namespace App\Policies;

use App\Models\FinancialAuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinancialAuditLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, FinancialAuditLog $log): bool
    {
        return $user->business_id === $log->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, FinancialAuditLog $log): bool
    {
        return $user->business_id === $log->business_id;
    }

    public function delete(User $user, FinancialAuditLog $log): bool
    {
        return false; // Financial audit logs should not be deletable
    }
}