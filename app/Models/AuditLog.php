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

    /** User behind the log entry (controller views load user:id,name,email). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'actor_user_id');
    }

    /*
     * Query scopes consumed by AuditService::getLogsForBusiness() and
     * getLogStatistics() (local fallback for the Supabase RPC views).
     */
    public function scopeForBusiness($query, ?int $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function scopeForAction($query, ?string $action)
    {
        return $action ? $query->where('action', $action) : $query;
    }

    public function scopeForUser($query, $userId)
    {
        return $userId ? $query->where('actor_user_id', $userId) : $query;
    }

    public function scopeForModel($query, ?string $modelType, $modelId = null)
    {
        if (! $modelType) {
            return $query;
        }

        return $query->where('model_type', $modelType)
            ->when($modelId, fn ($q) => $q->where('model_id', $modelId));
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));
    }
}
