<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'tax_id' => fake()->optional()->bothify('??-#######'),
            'payment_terms' => fake()->randomElement(['Net 30', 'Net 60', 'Net 90']),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
            'created_by' => 1,
        ];
    }
}
