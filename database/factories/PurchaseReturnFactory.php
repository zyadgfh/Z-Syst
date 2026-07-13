<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseReturnFactory extends Factory
{
    protected $model = PurchaseReturn::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'purchase_id' => Purchase::factory(),
            'invoice_no' => 'PR-'.strtoupper($this->faker->bothify('????####')),
            'return_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
