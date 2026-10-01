<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseStockFactory extends Factory
{
    protected $model = WarehouseStock::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(0, 500),
        ];
    }
}
