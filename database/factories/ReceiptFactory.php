<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Receipt;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'sale_id' => Sale::factory(),
            'receipt_number' => 'RCP-' . $this->faker->unique()->numerify('######'),
            'type' => 'sale',
            'format' => 'thermal',
            'status' => 'generated',
            'data' => [],
        ];
    }
}
