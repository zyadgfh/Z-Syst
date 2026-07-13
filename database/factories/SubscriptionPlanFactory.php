<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Plan',
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'monthly_price' => $this->faker->randomFloat(2, 0, 199),
            'yearly_price' => $this->faker->randomFloat(2, 0, 1999),
            'max_users' => $this->faker->numberBetween(1, 50),
            'max_branches' => $this->faker->numberBetween(1, 20),
            'max_products' => $this->faker->numberBetween(100, 10000),
            'max_transactions_monthly' => $this->faker->numberBetween(100, 100000),
            'has_advanced_reports' => $this->faker->boolean(50),
            'has_insurance_integration' => $this->faker->boolean(50),
            'has_api_access' => $this->faker->boolean(50),
            'has_priority_support' => $this->faker->boolean(25),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
