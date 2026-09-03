<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'company_name' => fake()->company(),
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'tax_id' => fake()->numerify('###-###-###'),
            'license_number' => fake()->numerify('LIC-#####'),
            'rating' => fake()->randomFloat(2, 1, 5),
            'performance_score' => fake()->randomFloat(2, 0, 100),
            'payment_terms' => fake()->randomElement(['net_30', 'net_60', 'net_90', 'cod', 'prepaid']),
            'credit_limit' => fake()->randomFloat(2, 1000, 100000),
            'contract_start' => fake()->optional(0.7)->dateTimeBetween('-1 year', '+1 month'),
            'contract_end' => fake()->optional(0.7)->dateTimeBetween('+2 months', '+2 years'),
            'is_active' => fake()->boolean(85),
            'notes' => fake()->optional(0.5)->sentence(),
        ];
    }
}
