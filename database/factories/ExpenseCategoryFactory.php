<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

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
