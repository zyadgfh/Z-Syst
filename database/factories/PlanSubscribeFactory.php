<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Gateway;
use App\Models\Plan;
use App\Models\PlanSubscribe;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanSubscribeFactory extends Factory
{
    protected $model = PlanSubscribe::class;

    public function definition(): array
    {
        return [
            'price' => fake()->randomFloat(2, 0, 500),
            'notes' => fake()->sentence(),
            'plan_id' => Plan::factory(),
            'duration' => fake()->numberBetween(1, 365),
            'gateway_id' => Gateway::factory(),
            'business_id' => Business::factory(),
            'payment_status' => fake()->randomElement(['pending', 'paid', 'failed']),
        ];
    }
}
