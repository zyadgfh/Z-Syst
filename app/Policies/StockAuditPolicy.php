<?php

namespace App\Policies;

use App\Models\StockAudit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockAuditPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               $audit->status !== 'completed';
    }

    public function delete(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               $audit->status === 'pending';
    }

    public function start(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               $audit->status === 'pending';
    }

    public function complete(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               $audit->status === 'in_progress';
    }

    public function cancel(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               in_array($audit->status, ['pending', 'in_progress']);
    }

    public function addDetails(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               in_array($audit->status, ['pending', 'in_progress']);
    }

    public function postReconciliations(User $user, StockAudit $audit): bool
    {
        return $user->business_id === $audit->business_id &&
               $audit->status === 'completed';
    }
}