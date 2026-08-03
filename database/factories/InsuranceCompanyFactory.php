<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\InsuranceCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\\Models\\InsuranceCompany>
 */
class InsuranceCompanyFactory extends Factory
{
    protected $model = InsuranceCompany::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => $this->faker->company(),
            'code' => 'INS-' . $this->faker->unique()->numberBetween(1000, 9999),
            'contact_person' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'tax_id' => $this->faker->unique()->numerify('TAX####'),
            'status' => 'active',
            'integration_type' => 'manual',
            'default_coverage_percent' => 80.00,
            'default_copay_percent' => 20.00,
            'settlement_days' => 30,
            'notes' => $this->faker->sentence(),
            'metadata' => [],
        ];
    }
}
