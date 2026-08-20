<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Income;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncomeFactory extends Factory
{
    protected $model = Income::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'income_category_id' => null,
            'incomeFor' => fake()->words(3, true),
            'paymentType' => fake()->randomElement(['cash', 'card', 'transfer']),
            'referenceNo' => fake()->optional()->numerify('REF-#####'),
            'note' => fake()->optional()->sentence(),
            'incomeDate' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
