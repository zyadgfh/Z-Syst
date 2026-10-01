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
            'phone' => fake()->unique()->phoneNumber(),
            'address' => fake()->address(),
            'tax_id' => fake()->optional()->bothify('TAX-#########'),
            'license_number' => fake()->optional()->bothify('LIC-#####'),
            'rating' => fake()->randomFloat(1, 0, 5),
            'performance_score' => fake()->randomFloat(2, 0, 100),
            'payment_terms' => fake()->randomElement(['net_15', 'net_30', 'net_60']),
            'credit_limit' => fake()->randomFloat(2, 1000, 100000),
            'contract_start' => null,
            'contract_end' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
