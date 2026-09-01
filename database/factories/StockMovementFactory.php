<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'product_id' => Product::factory(),
            'stock_id' => Stock::factory(),
            'user_id' => User::factory(),
            'movement_type' => fake()->randomElement(['in', 'out', 'adjustment', 'transfer', 'return']),
            'quantity' => fake()->numberBetween(1, 100),
            'before_quantity' => fake()->numberBetween(0, 100),
            'after_quantity' => fake()->numberBetween(0, 100),
            'batch_no' => fake()->optional()->numerify('BAT-#####'),
            'expire_date' => fake()->optional()->dateTimeBetween('+1 month', '+1 year'),
            'reference_type' => fake()->randomElement(['App\\Models\\Sale', 'App\\Models\\Purchase', 'App\\Models\\StockTransfer']),
            'reference_id' => fake()->randomNumber(),
            'notes' => fake()->optional()->text(),
        ];
    }

    public function in(): self
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'in',
        ]);
    }

    public function out(): self
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'out',
        ]);
    }

    public function transfer(): self
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'transfer',
            'reference_type' => 'App\\Models\\StockTransfer',
        ]);
    }

    public function adjustment(): self
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'adjustment',
            'reference_type' => 'App\\Models\\StockAdjustment',
        ]);
    }

    public function sale(): self
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'out',
            'reference_type' => 'App\\Models\\Sale',
        ]);
    }

    public function purchase(): self
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'in',
            'reference_type' => 'App\\Models\\Purchase',
        ]);
    }

    public function returnMovement(): self
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'return',
            'reference_type' => fake()->randomElement(['App\\Models\\SaleReturn', 'App\\Models\\PurchaseReturn']),
        ]);
    }
}
