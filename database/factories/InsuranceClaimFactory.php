<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceClaimFactory extends Factory
{
    protected $model = InsuranceClaim::class;

    public function definition()
    {
        $serviceDate = fake()->dateTimeBetween('-6 months', 'now');
        $totalAmount = fake()->randomFloat(2, 10, 1000);
        $coveragePercent = fake()->numberBetween(70, 90);
        $coveredAmount = ($totalAmount * $coveragePercent) / 100;
        $patientResponsibility = $totalAmount - $coveredAmount;

        return [
            'business_id' => Business::factory(),
            'insurance_company_id' => InsuranceCompany::factory(),
            'insurance_policy_id' => InsurancePolicy::factory(),
            'sale_id' => null,
            'prescription_id' => null,
            'customer_id' => null,
            'user_id' => null,
            'claim_number' => 'CLM-'.date('Ymd').'-'.strtoupper(fake()->unique()->regexify('[A-Z0-9]{8}')),
            'service_date' => $serviceDate,
            'submission_date' => fake()->optional()->dateTimeBetween($serviceDate, 'now'),
            'total_amount' => $totalAmount,
            'covered_amount' => $coveredAmount,
            'patient_responsibility' => $patientResponsibility,
            'approved_amount' => fake()->optional(0.7)->randomFloat(2, 0, $coveredAmount),
            'paid_amount' => fake()->optional(0.5)->randomFloat(2, 0, $coveredAmount),
            'rejected_amount' => fake()->optional(0.1)->randomFloat(2, 0, $coveredAmount),
            'status' => fake()->randomElement(['draft', 'submitted', 'under_review', 'approved', 'partially_approved', 'rejected', 'paid', 'cancelled']),
            'rejection_reason' => fake()->optional()->sentence(),
            'external_reference' => fake()->optional()->numerify('EXT########'),
            'settlement_date' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'notes' => fake()->optional()->paragraph(),
            'line_items' => fake()->optional()->randomElements([
                ['product_id' => 1, 'amount' => 50, 'coverage' => 80],
                ['product_id' => 2, 'amount' => 30, 'coverage' => 90],
            ], rand(1, 3)),
        ];
    }
}
