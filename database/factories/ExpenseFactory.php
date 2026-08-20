<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'expense_category_id' => null,
            'expanseFor' => fake()->words(3, true),
            'paymentType' => fake()->randomElement(['cash', 'card', 'transfer']),
            'referenceNo' => fake()->optional()->numerify('REF-#####'),
            'note' => fake()->optional()->sentence(),
            'expenseDate' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
