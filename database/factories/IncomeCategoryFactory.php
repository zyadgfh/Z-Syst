<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\IncomeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncomeCategoryFactory extends Factory
{
    protected $model = IncomeCategory::class;

    public function definition(): array
    {
        return [
            'categoryName' => fake()->word(),
            'company_id' => Company::factory(),
            'categoryDescription' => fake()->sentence(),
            'status' => fake()->boolean(90),
        ];
    }
}
