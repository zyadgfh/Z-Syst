<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\InsurancePolicy;
use App\Models\InsuranceCompany;
use App\Models\Business;

class InsurancePolicyFactory extends Factory
{
    protected $model = InsurancePolicy::class;

    public function definition()
    {
        $startDate = fake()->dateTimeBetween('-1 year', 'now');
        $endDate = (clone $startDate)->modify('+1 year');

        return [
            'business_id' => Business::factory(),
            'insurance_company_id' => InsuranceCompany::factory(),
            'customer_id' => null,
            'policy_number' => 'POL-' . date('Ymd') . '-' . strtoupper(fake()->unique()->regexify('[A-Z0-9]{6}')),
            'member_id' => fake()->optional()->numerify('MEM########'),
            'card_number' => fake()->optional()->creditCardNumber(),
            'holder_name' => fake()->name(),
            'holder_dob' => fake()->dateBetween('-80 years', '-18 years'),
            'holder_gender' => fake()->randomElement(['male', 'female', 'other']),
            'holder_phone' => fake()->phoneNumber(),
            'holder_email' => fake()->email(),
            'holder_address' => fake()->address(),
            'plan_type' => fake()->randomElement(['individual', 'family', 'corporate', 'government']),
            'status' => 'active',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'annual_limit' => fake()->randomFloat(2, 1000, 50000),
            'used_amount' => fake()->randomFloat(2, 0, 5000),
            'coverage_percent' => fake()->numberBetween(70, 100),
            'copay_percent' => fake()->numberBetween(0, 30),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
