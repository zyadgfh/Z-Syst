<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'currency',
        'billing_cycle', 'trial_days', 'is_active', 'is_default',
        'features', 'limits', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'features' => 'array',
        'limits' => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeMonthly($query)
    {
        return $query->where('billing_cycle', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('billing_cycle', 'yearly');
    }

    public function getYearlyPriceAttribute(): float
    {
        return $this->billing_cycle === 'yearly' ? $this->price : $this->price * 12;
    }

    public function getMonthlyPriceAttribute(): float
    {
        return $this->billing_cycle === 'monthly' ? $this->price : $this->price / 12;
    }
}
