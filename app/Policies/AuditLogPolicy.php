<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('audit-logs-read');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasPermissionTo('audit-logs-show');
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return $user->hasPermissionTo('audit-logs-delete');
    }
}
