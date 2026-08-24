<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingAuditLog extends Model
{
    use HasFactory;

    protected $table = 'settings_audit_logs';

    protected $fillable = [
        'setting_definition_id',
        'key',
        'name',
        'scope_type',
        'scope_id',
        'old_value',
        'new_value',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(SettingDefinition::class, 'setting_definition_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a human-readable scope label.
     */
    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope_type) {
            'system' => __('System Default'),
            'organization' => __('Organization'),
            'branch' => __('Branch'),
            'role' => __('Role'),
            'user' => __('User'),
            default => $this->scope_type,
        };
    }

    /**
     * Scope by setting key.
     */
    public function scopeForKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Scope by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by scope type and id.
     */
    public function scopeForScope($query, string $scopeType, $scopeId = null)
    {
        $query->where('scope_type', $scopeType);
        if ($scopeId !== null) {
            $query->where('scope_id', $scopeId);
        }

        return $query;
    }

    /**
     * Scope by date range.
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
