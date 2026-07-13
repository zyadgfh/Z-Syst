<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseDetailsFactory extends Factory
{
    protected $model = PurchaseDetails::class;

    public function definition(): array
    {
        return [
            'purchase_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'purchase_without_tax' => $this->faker->randomFloat(2, 10, 200),
            'purchase_with_tax' => $this->faker->randomFloat(2, 10, 250),
            'profit_percent' => $this->faker->randomFloat(2, 0, 50),
            'sales_price' => $this->faker->randomFloat(2, 15, 300),
            'wholesale_price' => $this->faker->randomFloat(2, 10, 250),
            'quantities' => $this->faker->numberBetween(1, 50),
            'batch_no' => $this->faker->bothify('BATCH-#####'),
            'expire_date' => $this->faker->optional()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
        ];
    }
}
