<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 100);
        $unitPrice = fake()->randomFloat(2, 5, 500);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'received_quantity' => 0,
            'pending_quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => 0,
            'tax' => 0,
            'total' => $quantity * $unitPrice,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
