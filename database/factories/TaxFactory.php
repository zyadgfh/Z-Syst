<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->randomElement(['VAT', 'Sales Tax', 'Service Tax']),
            'rate' => fake()->randomFloat(2, 1, 25),
            'sub_tax' => fake()->randomFloat(2, 0, 5),
            'status' => true,
        ];
    }
}
