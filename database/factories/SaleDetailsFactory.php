<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleDetailsFactory extends Factory
{
    protected $model = SaleDetails::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $price = $this->faker->randomFloat(2, 5, 200);

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'price' => $price,
            'lossProfit' => $this->faker->randomFloat(2, -50, 100),
            'batch_no' => $this->faker->optional()->bothify('BATCH-#####'),
            'quantities' => $quantity,
            'purchase_price' => $this->faker->randomFloat(2, 1, 100),
            'expire_date' => $this->faker->optional()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
        ];
    }
}
