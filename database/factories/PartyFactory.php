<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartyFactory extends Factory
{
    protected $model = Party::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['customer', 'supplier']),
            'name' => fake()->company(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'due' => fake()->randomFloat(2, 0, 10000),
            'image' => null,
            'status' => fake()->boolean(90),
            'address' => fake()->address(),
            'company_id' => Company::factory(),
            'opening_balance' => fake()->randomFloat(2, 0, 5000),
        ];
    }
}
