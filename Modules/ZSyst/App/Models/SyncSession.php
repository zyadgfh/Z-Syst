<?php

namespace Modules\ZSyst\App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncSession extends Model
{
    protected $fillable = [
        'device_id',
        'status',
        'last_sync_at',
        'payload',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
    ];
}
