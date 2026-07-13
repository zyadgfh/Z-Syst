<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashRegisterFactory extends Factory
{
    protected $model = CashRegister::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'user_id' => User::factory(),
            'opening_balance' => fake()->randomFloat(2, 0, 1000),
            'closing_balance' => fake()->randomFloat(2, 0, 1000),
            'total_sales' => fake()->randomFloat(2, 0, 5000),
            'total_returns' => fake()->randomFloat(2, 0, 500),
            'total_expenses' => fake()->randomFloat(2, 0, 500),
            'expected_balance' => fake()->randomFloat(2, 0, 1000),
            'difference' => fake()->randomFloat(2, -100, 100),
            'status' => fake()->boolean(90),
            'notes' => fake()->optional()->sentence(),
            'opened_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'closed_at' => fake()->optional()->dateTimeBetween('now', '+1 day'),
        ];
    }
}
