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
        $quantityOrdered = $this->faker->numberBetween(1, 100);
        $unitCost = $this->faker->randomFloat(2, 5, 200);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'quantity_ordered' => $quantityOrdered,
            'quantity_received' => $this->faker->numberBetween(0, $quantityOrdered),
            'unit_cost' => $unitCost,
            'discount' => $this->faker->randomFloat(2, 0, 50),
            'tax' => $this->faker->randomFloat(2, 0, 20),
            'total' => ($unitCost * $quantityOrdered) - $this->faker->randomFloat(2, 0, 50) + $this->faker->randomFloat(2, 0, 20),
        ];
    }
}
