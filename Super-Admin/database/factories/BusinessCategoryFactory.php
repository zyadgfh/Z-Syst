<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' ' . $this->faker->randomElement(['Store', 'Shop', 'Mart', 'Center', 'Outlet']) . ' ' . $this->faker->numberBetween(1, 10000),
            'description' => $this->faker->sentence(),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
