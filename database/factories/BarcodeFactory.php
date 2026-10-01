<?php

namespace Database\Factories;

use App\Models\Barcode;
use App\Models\Business;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class BarcodeFactory extends Factory
{
    protected $model = Barcode::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'business_id' => Business::factory(),
            'barcode_number' => fake()->unique()->numerify('#############'),
            'barcode_type' => 'CODE128',
            'print_status' => Barcode::STATUS_NOT_PRINTED,
            'print_count' => 0,
            'size' => 'standard',
            'is_active' => true,
        ];
    }
}
