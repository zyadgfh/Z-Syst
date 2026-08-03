<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'description' => 'created ' . get_class($model),
                'meta' => $model->toArray(),
                'ip_address' => request()->ip() ?? null,
                'user_agent' => request()->userAgent() ?? null,
            ]);
        });

        static::updated(function ($model) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'description' => 'updated ' . get_class($model),
                'meta' => [
                    'old' => $model->getOriginal(),
                    'new' => $model->getAttributes(),
                ],
                'ip_address' => request()->ip() ?? null,
                'user_agent' => request()->userAgent() ?? null,
            ]);
        });

        static::deleted(function ($model) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'description' => 'deleted ' . get_class($model),
                'meta' => $model->toArray(),
                'ip_address' => request()->ip() ?? null,
                'user_agent' => request()->userAgent() ?? null,
            ]);
        });
    }
}
