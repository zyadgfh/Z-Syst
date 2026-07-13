<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncomeFactory extends Factory
{
    protected $model = Income::class;

    public function definition(): array
    {
        return [
            'income_category_id' => IncomeCategory::factory(),
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'incomeFor' => fake()->sentence(2),
            'paymentType' => fake()->randomElement(['cash', 'bank', 'card']),
            'referenceNo' => fake()->bothify('INC-#####'),
            'note' => fake()->sentence(),
            'incomeDate' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
