<?php

namespace Database\Factories;

use App\Models\Gateway;
use Illuminate\Database\Eloquent\Factories\Factory;

class GatewayFactory extends Factory
{
    protected $model = Gateway::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'mode' => 'live',
            'data' => null,
            'image' => null,
            'status' => 'active',
            'charge' => 0,
            'is_manual' => false,
            'namespace' => null,
            'accept_img' => null,
            'manual_data' => null,
            'currency_id' => null,
            'instructions' => null,
            'phone_required' => false,
        ];
    }
}
