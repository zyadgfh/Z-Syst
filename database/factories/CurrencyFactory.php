<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        $code = fake()->currencyCode();

        return [
            'name' => fake()->currencyCode() . ' Currency',
            'country_name' => fake()->country(),
            'code' => $code,
            'rate' => fake()->randomFloat(4, 0.1, 5),
            'symbol' => fake()->randomElement(['$', '€', '£', '¥', '₹']),
            'position' => fake()->randomElement(['left', 'right']),
            'status' => fake()->boolean(90),
            'is_default' => false,
        ];
    }
}
