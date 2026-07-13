<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleReturnFactory extends Factory
{
    protected $model = SaleReturn::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'sale_id' => Sale::factory(),
            'invoice_no' => 'SR-'.strtoupper($this->faker->bothify('????####')),
            'return_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
