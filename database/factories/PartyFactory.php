<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartyFactory extends Factory
{
    protected $model = Party::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'type' => fake()->randomElement(['supplier', 'customer']),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->phoneNumber(),
            'address' => fake()->address(),
            'status' => true,
            'opening_balance' => fake()->randomFloat(2, 0, 1000),
            'due' => fake()->randomFloat(2, 0, 500),
        ];
    }
}
