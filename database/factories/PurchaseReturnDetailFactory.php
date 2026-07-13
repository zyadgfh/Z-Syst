<?php

namespace Database\Factories;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Models\PurchaseDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseReturnDetailFactory extends Factory
{
    protected $model = PurchaseReturnDetail::class;

    public function definition(): array
    {
        return [
            'business_id' => 1,
            'purchase_return_id' => PurchaseReturn::factory(),
            'purchase_detail_id' => PurchaseDetails::factory(),
            'return_amount' => $this->faker->randomFloat(2, 1, 200),
            'return_qty' => $this->faker->numberBetween(1, 20),
        ];
    }
}
