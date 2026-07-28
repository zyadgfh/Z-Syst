<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'prediction_enabled',
        'forecast_period',
        'forecast_days',
        'historical_months',
        'prediction_method',
        'seasonal_adjustment',
        'safety_stock_multiplier',
        'lead_time_days',
        'confidence_threshold',
        'auto_order_enabled',
        'meta',
    ];

    protected $casts = [
        'prediction_enabled' => 'boolean',
        'seasonal_adjustment' => 'boolean',
        'auto_order_enabled' => 'boolean',
        'safety_stock_multiplier' => 'decimal:2',
        'confidence_threshold' => 'decimal:2',
        'meta' => 'json',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public static function getForBusiness(int $businessId): self
    {
        return static::firstOrCreate(
            ['business_id' => $businessId],
            [
                'prediction_enabled' => true,
                'forecast_period' => 'daily',
                'forecast_days' => 30,
                'historical_months' => 6,
                'prediction_method' => 'combined',
                'seasonal_adjustment' => true,
                'safety_stock_multiplier' => 1.5,
                'lead_time_days' => 7,
                'confidence_threshold' => 0.7,
                'auto_order_enabled' => false,
            ]
        );
    }
}

