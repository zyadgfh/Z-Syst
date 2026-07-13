<?php

namespace Database\Factories;

use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrnItemFactory extends Factory
{
    protected $model = GrnItem::class;

    public function definition(): array
    {
        return [
            'grn_id' => GoodsReceivedNote::factory(),
            'product_id' => Product::factory(),
            'batch_number' => fake()->bothify('BATCH-#####'),
            'quantity_received' => fake()->randomFloat(2, 1, 100),
            'expiry_date' => fake()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'manufacturing_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'unit_cost' => fake()->randomFloat(2, 1, 100),
            'rack_location' => fake()->word(),
        ];
    }
}
