<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment Method Model
 * 
 * إعدادات طرق الدفع لكل شركة
 */
class PaymentMethod extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'gateway',
        'gateway_integration_id',
        'is_active',
        'is_pos_enabled',
        'is_online_enabled',
        'min_amount',
        'max_amount',
        'configuration',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_pos_enabled' => 'boolean',
        'is_online_enabled' => 'boolean',
        'configuration' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if method is available for POS
     */
    public function isAvailableForPOS(): bool
    {
        return $this->is_active && $this->is_pos_enabled;
    }

    /**
     * Check if method is available online
     */
    public function isAvailableOnline(): bool
    {
        return $this->is_active && $this->is_online_enabled;
    }

    /**
     * Check if amount is within limits
     */
    public function isAmountValid(float $amount): bool
    {
        return $amount >= ($this->min_amount ?? 1) && $amount <= ($this->max_amount ?? 50000);
    }

    /**
     * Scope active methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for POS
     */
    public function scopePOS($query)
    {
        return $query->where('is_pos_enabled', true);
    }

    /**
     * Scope for online
     */
    public function scopeOnline($query)
    {
        return $query->where('is_online_enabled', true);
    }

    /**
     * Get by code
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}