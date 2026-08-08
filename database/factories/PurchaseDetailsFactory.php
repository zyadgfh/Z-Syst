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
            'purchase_without_tax' => fake()->randomFloat(2, 1, 100),
            'purchase_with_tax' => fake()->randomFloat(2, 1, 100),
            'profit_percent' => fake()->randomFloat(2, 5, 50),
            'sales_price' => fake()->randomFloat(2, 10, 200),
            'wholesale_price' => fake()->randomFloat(2, 8, 150),
            'quantities' => fake()->randomFloat(2, 1, 50),
            'batch_no' => fake()->bothify('BATCH-####'),
        ];
    }
}
