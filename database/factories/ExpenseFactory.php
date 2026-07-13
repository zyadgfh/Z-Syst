<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'expense_category_id' => ExpenseCategory::factory(),
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'expanseFor' => fake()->word(),
            'paymentType' => fake()->randomElement(['cash', 'bank', 'mobile']),
            'referenceNo' => fake()->bothify('EXP-#####'),
            'note' => fake()->sentence(),
            'expenseDate' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
