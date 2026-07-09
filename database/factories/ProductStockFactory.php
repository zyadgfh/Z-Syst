<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductStockFactory extends Factory
{
    protected $model = ProductStock::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'product_id' => Product::factory(),
            'branch_id' => Branch::factory(),
            'quantity' => $this->faker->numberBetween(10, 500),
            'reorder_level' => $this->faker->numberBetween(5, 50),
            'reorder_quantity' => $this->faker->numberBetween(20, 100),
            'batch_number' => $this->faker->bothify('BATCH-#####'),
            'expiry_date' => $this->faker->dateTimeBetween('+1 month', '+2 years'),
            'is_active' => true,
        ];
    }
}
