<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Plan;
use App\Models\PlanSubscribe;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanSubscribeFactory extends Factory
{
    protected $model = PlanSubscribe::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'business_id' => Business::factory(),
            'gateway_id' => null,
            'price' => fake()->randomFloat(2, 10, 500),
            'payment_status' => 'paid',
            'duration' => 30,
            'notes' => null,
        ];
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'unpaid',
        ]);
    }
}
