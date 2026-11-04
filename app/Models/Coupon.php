<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Coupon - table `coupons` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Coupon extends Model
{
    protected $table = 'coupons';

    protected $fillable = [
        'business_id',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_count',
        'per_customer_limit',
        'start_at',
        'end_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'per_customer_limit' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
