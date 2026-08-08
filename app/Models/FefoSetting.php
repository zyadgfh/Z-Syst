<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FefoSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'fefo_enabled',
        'deduction_mode',
        'expiry_grace_days',
        'auto_deduct_expired_stock',
        'notify_on_fefo_deduction',
        'min_stock_for_fefo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'fefo_enabled' => 'boolean',
        'auto_deduct_expired_stock' => 'boolean',
        'notify_on_fefo_deduction' => 'boolean',
        'min_stock_for_fefo' => 'integer',
        'expiry_grace_days' => 'integer',
    ];

    /**
     * Get the business that owns the FEFO settings.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the default FEFO settings for a business.
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
