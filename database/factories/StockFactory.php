<?php

namespace Database\Factories;

use App\Models\Stock;
use App\Models\Product;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'product_id' => Product::factory(),
            'productStock' => fake()->randomFloat(2, 10, 500),
            'batch_no' => fake()->unique()->bothify('BATCH-####'),
            'expire_date' => fake()->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_date' => fake()->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function expiringSoon(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_date' => now()->addDays(fake()->numberBetween(1, $days))->format('Y-m-d'),
        ]);
    }
}