<?php

namespace Database\Factories;

use App\Models\SaleReturn;
use App\Models\SaleReturnDetails;
use App\Models\SaleDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleReturnDetailsFactory extends Factory
{
    protected $model = SaleReturnDetails::class;

    public function definition(): array
    {
        return [
            'business_id' => 1,
            'sale_return_id' => SaleReturn::factory(),
            'sale_detail_id' => SaleDetails::factory(),
            'return_amount' => $this->faker->randomFloat(2, 1, 200),
            'return_qty' => $this->faker->numberBetween(1, 10),
        ];
    }
}
