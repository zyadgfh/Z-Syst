<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FefoSetting - table `fefo_settings` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class FefoSetting extends Model
{
    protected $table = 'fefo_settings';

    protected $fillable = [
        'business_id',
        'fefo_enabled',
        'deduction_mode',
        'expiry_grace_days',
        'auto_deduct_expired_stock',
        'notify_on_fefo_deduction',
        'min_stock_for_fefo',
    ];

    protected $casts = [
        'fefo_enabled' => 'boolean',
        'expiry_grace_days' => 'integer',
        'auto_deduct_expired_stock' => 'boolean',
        'notify_on_fefo_deduction' => 'boolean',
        'min_stock_for_fefo' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Per-business FEFO settings, created on first access with defaults
     * (mirrors PredictionSetting::getForBusiness()).
     */
    public static function getForBusiness(int $businessId): self
    {
        return static::firstOrCreate(
            ['business_id' => $businessId],
            [
                'fefo_enabled' => true,
                'deduction_mode' => 'automatic',
                'expiry_grace_days' => 30,
                'auto_deduct_expired_stock' => false,
                'notify_on_fefo_deduction' => true,
                'min_stock_for_fefo' => 0,
            ]
        );
    }
}
