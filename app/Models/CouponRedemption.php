<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CouponRedemption - table `coupon_redemptions` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class CouponRedemption extends Model
{
    protected $table = 'coupon_redemptions';

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'coupon_id',
        'sale_id',
        'party_id',
        'discount_amount',
        'redeemed_at',
        'created_by',
    ];

    protected $casts = [
        'coupon_id' => 'integer',
        'sale_id' => 'integer',
        'party_id' => 'integer',
        'discount_amount' => 'decimal:2',
        'redeemed_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }
}
