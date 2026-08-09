<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition()
    {
        return [
            'subscriptionName' => fake()->words(3, true),
            'duration' => fake()->numberBetween(30, 365),
            'subscriptionPrice' => fake()->randomFloat(2, 10, 1000),
            'offerPrice' => fake()->optional(0.7)->randomFloat(2, 5, 500),
            'status' => 1,
            'features' => json_encode(fake()->sentences(3)),
        ];
    }
}
