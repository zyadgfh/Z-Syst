<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BatchLot;
use App\Models\Product;
use App\Models\Business;

class BatchLotFactory extends Factory
{
    protected $model = BatchLot::class;

    public function definition()
    {
        $manufactureDate = fake()->dateTimeBetween('-2 years', '-6 months');
        $expiryDate = (clone $manufactureDate)->modify('+2 years');

        return [
            'business_id' => Business::factory(),
            'product_id' => Product::factory(),
            'batch_number' => 'BATCH-' . strtoupper(fake()->unique()->regexify('[A-Z0-9]{8}')),
            'lot_number' => 'LOT-' . strtoupper(fake()->unique()->regexify('[A-Z0-9]{6}')),
            'manufacture_date' => $manufactureDate,
            'expiry_date' => $expiryDate,
            'recall_date' => null,
            'supplier_name' => fake()->company(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
