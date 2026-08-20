<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialTransactionFactory extends Factory
{
    protected $model = FinancialTransaction::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['revenue', 'expense', 'transfer']),
            'category' => fake()->randomElement([
                'sales',
                'purchases',
                'salary',
                'rent',
                'utilities',
                'marketing',
                'other',
            ]),
            'amount' => fake()->randomFloat(2, 10, 10000),
            'description' => fake()->sentence(),
            'transaction_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'reference_type' => fake()->optional()->randomElement(['App\Models\Sale', 'App\Models\Purchase', 'App\Models\Expense']),
            'reference_id' => fake()->optional()->randomNumber(),
            'payment_method' => fake()->randomElement(['cash', 'card', 'bank_transfer', 'check']),
            'status' => fake()->randomElement(['pending', 'completed', 'cancelled']),
            'notes' => fake()->optional()->text(),
        ];
    }

    public function revenue(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'revenue',
            'category' => fake()->randomElement(['sales', 'services', 'other']),
        ]);
    }

    public function expense(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'category' => fake()->randomElement(['purchases', 'salary', 'rent', 'utilities', 'marketing', 'other']),
        ]);
    }

    public function transfer(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'transfer',
            'category' => 'transfer',
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function cash(): self
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
        ]);
    }

    public function card(): self
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'card',
        ]);
    }

    public function bankTransfer(): self
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'bank_transfer',
        ]);
    }
}
