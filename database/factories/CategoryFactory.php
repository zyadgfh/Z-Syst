<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'categoryName' => fake()->word(),
            'status' => true,
        ];
    }
}
