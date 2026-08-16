<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'points_per_currency',
        'min_points_to_redeem',
        'is_active',
    ];

    protected $casts = [
        'points_per_currency' => 'integer',
        'min_points_to_redeem' => 'integer',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    /**
     * Scope for business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for active programs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate points for a given amount
     */
    public function calculatePoints(float $amount): int
    {
        return (int) floor($amount * $this->points_per_currency);
    }

    /**
     * Calculate currency value for points
     */
    public function calculateCurrencyValue(int $points): float
    {
        if ($this->points_per_currency === 0) {
            return 0;
        }

        return $points / $this->points_per_currency;
    }

    /**
     * Check if customer has enough points for reward
     */
    public function canRedeemReward(int $customerPoints): bool
    {
        return $customerPoints >= $this->min_points_to_redeem;
    }
}
