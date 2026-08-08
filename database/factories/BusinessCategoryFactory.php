<?php

namespace Database\Factories;

use App\Models\BusinessCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessCategoryFactory extends Factory
{
    protected $model = BusinessCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' Pharmacy',
            'description' => fake()->sentence(),
            'status' => true,
        ];
    }
}
