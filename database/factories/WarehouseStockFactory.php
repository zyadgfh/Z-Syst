<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseStockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(0, 1000),
        ];
    }
}
