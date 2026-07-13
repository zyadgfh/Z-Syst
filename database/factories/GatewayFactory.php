<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Gateway;
use Illuminate\Database\Eloquent\Factories\Factory;

class GatewayFactory extends Factory
{
    protected $model = Gateway::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Gateway',
            'mode' => fake()->randomElement(['live', 'test']),
            'data' => ['client_id' => fake()->bothify('ID-####'), 'secret' => fake()->sha256()],
            'image' => null,
            'status' => fake()->boolean(90),
            'charge' => fake()->randomFloat(2, 0, 100),
            'is_manual' => fake()->boolean(30),
            'namespace' => 'App\\Payments\\' . fake()->word(),
            'accept_img' => fake()->boolean(50),
            'manual_data' => [],
            'currency_id' => Currency::factory(),
            'instructions' => fake()->sentence(),
            'phone_required' => fake()->boolean(50),
        ];
    }
}
