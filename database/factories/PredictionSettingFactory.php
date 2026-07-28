<?php

namespace Database\Factories;

use App\Models\PredictionSetting;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class PredictionSettingFactory extends Factory
{
    protected $model = PredictionSetting::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
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
        ];
    }
}