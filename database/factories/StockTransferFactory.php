<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockTransferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'status' => fake()->randomElement(['pending', 'completed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
