<?php

namespace Database\Factories;

use App\Models\InsuranceCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceCompanyFactory extends Factory
{
    protected $model = InsuranceCompany::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'discount_percentage' => fake()->randomFloat(2, 0, 30),
            'contract_terms' => fake()->optional()->sentence(),
            'payment_terms' => fake()->randomElement(['Net 30', 'Net 60', 'Net 90']),
            'code' => strtoupper(fake()->bothify('???###')),
            'is_active' => true,
            'created_by' => 1,
        ];
    }
}
