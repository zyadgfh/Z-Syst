<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'company_id' => 1,
            'supplier_code' => 'SUP-' . strtoupper(Str::random(6)),
            'name' => fake()->company(),
            'legal_name' => fake()->company(),
            'tax_id' => fake()->optional()->bothify('??-#######'),
            'contact_person' => fake()->name(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'phone_secondary' => fake()->optional()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'website' => fake()->url(),
            'payment_terms' => fake()->randomElement(['Net 30', 'Net 60', 'Net 90']),
            'credit_limit' => fake()->randomFloat(2, 0, 100000),
            'current_balance' => fake()->randomFloat(2, 0, 50000),
            'total_purchases' => fake()->randomFloat(2, 0, 500000),
            'rating' => fake()->numberBetween(0, 5),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
