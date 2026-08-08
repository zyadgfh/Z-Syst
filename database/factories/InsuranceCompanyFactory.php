<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\InsuranceCompany;
use App\Models\Business;

class InsuranceCompanyFactory extends Factory
{
    protected $model = InsuranceCompany::class;

    public function definition()
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->company(),
            'code' => 'INS-' . strtoupper(fake()->unique()->regexify('[A-Z0-9]{8}')),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'tax_id' => fake()->taxId(),
            'status' => 'active',
            'integration_type' => 'manual',
            'api_endpoint' => null,
            'api_credentials' => null,
            'default_coverage_percent' => fake()->numberBetween(70, 90),
            'default_copay_percent' => fake()->numberBetween(10, 30),
            'settlement_days' => fake()->numberBetween(15, 45),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
