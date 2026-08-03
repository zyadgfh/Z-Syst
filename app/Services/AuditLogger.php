<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(string $action, string $description, array $meta = []): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta' => $meta,
        ]);
    }
}
