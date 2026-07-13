<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'status' => fake()->boolean(90),
            'duration' => fake()->numberBetween(1, 365),
            'offerPrice' => fake()->randomFloat(2, 0, 100),
            'subscriptionName' => fake()->word() . ' Plan',
            'subscriptionPrice' => fake()->randomFloat(2, 0, 500),
            'features' => [
                'max_users' => fake()->numberBetween(1, 50),
                'storage' => fake()->randomElement(['5GB', '10GB', '50GB']),
                'support' => fake()->randomElement(['email', 'phone', 'chat']),
            ],
        ];
    }
}
