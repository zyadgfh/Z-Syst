<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 5, 50);
        $discount = $this->faker->randomFloat(2, 0, 5);
        $tax = $this->faker->randomFloat(2, 0, 2);
        $total = ($unitPrice * $quantity) - $discount + $tax;

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'batch_number' => $this->faker->bothify('BATCH-#####'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
        ];
    }
}
