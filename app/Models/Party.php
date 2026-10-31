<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Party - table `parties` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Party extends Model
{
    protected $table = 'parties';

    protected $fillable = [
        'business_id',
        'name',
        'email',
        'type',
        'phone',
        'due',
        'opening_balance',
        'address',
        'image',
        'status',
    ];

    protected $casts = [
        'due' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'party_id');
    }

    public function customerInteractions(): HasMany
    {
        return $this->hasMany(CustomerInteraction::class, 'party_id');
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'party_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'party_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'party_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'party_id');
    }
}
