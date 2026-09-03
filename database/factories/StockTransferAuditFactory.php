<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferAudit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockTransferAuditFactory extends Factory
{
    protected $model = StockTransferAudit::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'stock_transfer_id' => StockTransfer::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['created', 'completed', 'cancelled', 'failed']),
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'from_stock_before' => fake()->numberBetween(0, 200),
            'from_stock_after' => fake()->numberBetween(0, 200),
            'to_stock_before' => fake()->numberBetween(0, 200),
            'to_stock_after' => fake()->numberBetween(0, 200),
            'notes' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['success', 'failed']),
            'metadata' => null,
        ];
    }
}
