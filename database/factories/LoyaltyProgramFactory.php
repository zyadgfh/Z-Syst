<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\LoyaltyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyProgramFactory extends Factory
{
    protected $model = LoyaltyProgram::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->words(2, true),
            'points_per_currency' => fake()->numberBetween(1, 10),
            'min_points_to_redeem' => fake()->numberBetween(50, 500),
            'is_active' => true,
        ];
    }
}
