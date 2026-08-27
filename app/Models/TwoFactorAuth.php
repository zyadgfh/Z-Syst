<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorAuth extends Model
{
    use HasFactory;

    protected $table = 'two_factor_auth';

    protected $fillable = [
        'user_id',
        'secret',
        'recovery_codes',
        'is_enabled',
        'enabled_at',
        'last_used_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'enabled_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
        'recovery_codes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if 2FA is enabled for this user.
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled && $this->secret !== null;
    }

    /**
     * Mark 2FA as enabled.
     */
    public function markEnabled(): void
    {
        $this->update([
            'is_enabled' => true,
            'enabled_at' => now(),
        ]);
    }

    /**
     * Mark 2FA as disabled.
     */
    public function markDisabled(): void
    {
        $this->update([
            'is_enabled' => false,
            'enabled_at' => null,
        ]);
    }

    /**
     * Record a successful 2FA verification.
     */
    public function recordVerification(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope: Only enabled 2FA records.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
