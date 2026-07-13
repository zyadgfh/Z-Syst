<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'categoryName' => fake()->word(),
            'company_id' => Company::factory(),
            'description' => fake()->sentence(),
            'status' => fake()->boolean(90),
        ];
    }
}
