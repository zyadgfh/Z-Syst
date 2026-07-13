<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrderReturn;
use App\Models\PurchaseOrderReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderReturnItemFactory extends Factory
{
    protected $model = PurchaseOrderReturnItem::class;

    public function definition(): array
    {
        $quantityReturned = $this->faker->numberBetween(1, 20);
        $unitCost = $this->faker->randomFloat(2, 5, 200);

        return [
            'purchase_order_return_id' => PurchaseOrderReturn::factory(),
            'product_id' => Product::factory(),
            'quantity_returned' => $quantityReturned,
            'unit_cost' => $unitCost,
            'total' => $unitCost * $quantityReturned,
            'batch_number' => $this->faker->optional()->bothify('BATCH-#####'),
        ];
    }
}
