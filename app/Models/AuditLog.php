<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog - table `audit_logs` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'business_id',
        'actor_user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'model_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'actor_user_id');
    }
}
