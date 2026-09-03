<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Supplier;
use App\Models\SupplierPerformance;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierPerformanceFactory extends Factory
{
    protected $model = SupplierPerformance::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'business_id' => Business::factory(),
            'on_time_delivery_rate' => fake()->randomFloat(2, 0, 100),
            'quality_score' => fake()->randomFloat(2, 0, 100),
            'price_competitiveness' => fake()->randomFloat(2, 0, 100),
            'responsiveness' => fake()->randomFloat(2, 0, 100),
            'total_orders' => fake()->numberBetween(0, 500),
            'total_disputes' => fake()->numberBetween(0, 50),
            'calculated_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
