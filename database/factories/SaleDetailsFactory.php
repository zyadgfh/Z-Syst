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
        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'price' => fake()->randomFloat(2, 5, 200),
            'quantities' => fake()->randomFloat(2, 1, 20),
            'purchase_price' => fake()->randomFloat(2, 2, 100),
            'batch_no' => fake()->bothify('BATCH-####'),
        ];
    }
}
