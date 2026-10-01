<?php

namespace Database\Factories;

use App\Models\GoodsReceivedNote;
use App\Models\GRNItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class GRNItemFactory extends Factory
{
    protected $model = GRNItem::class;

    public function definition(): array
    {
        $ordered = fake()->numberBetween(10, 100);

        return [
            'grn_id' => GoodsReceivedNote::factory(),
            'product_id' => Product::factory(),
            'ordered_quantity' => $ordered,
            'received_quantity' => $ordered,
            'accepted_quantity' => 0,
            'rejected_quantity' => 0,
            'batch_number' => fake()->optional()->bothify('BATCH-#####'),
            'expiry_date' => now()->addYear(),
            'purchase_price' => fake()->randomFloat(2, 1, 200),
        ];
    }
}
