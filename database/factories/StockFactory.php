<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'product_id' => Product::factory(),
            'productStock' => $this->faker->numberBetween(1, 200),
            'batch_no' => $this->faker->optional()->bothify('BATCH-#####'),
            'expire_date' => $this->faker->optional()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
        ];
    }
}
