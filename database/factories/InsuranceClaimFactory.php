<?php

namespace Database\Factories;

use App\Models\InsuranceClaim;
use App\Models\Patient;
use App\Models\InsuranceCompany;
use App\Models\InsurancePlan;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceClaimFactory extends Factory
{
    protected $model = InsuranceClaim::class;

    public function definition(): array
    {
        return [
            'claim_number' => 'CLAIM-' . strtoupper(fake()->bothify('????####')),
            'amount_claimed' => fake()->randomFloat(2, 10, 1000),
            'amount_approved' => fake()->optional()->randomFloat(2, 10, 1000),
            'co_pay_amount' => fake()->randomFloat(2, 0, 100),
            'settlement_amount' => fake()->optional()->randomFloat(2, 10, 1000),
            'status' => fake()->randomElement(['pending', 'submitted', 'approved', 'rejected', 'paid', 'partially_paid']),
            'notes' => fake()->optional()->sentence(),
            'rejection_reason' => fake()->optional()->sentence(),
            'submitted_at' => fake()->optional()->dateTime(),
            'approved_at' => fake()->optional()->dateTime(),
            'settled_at' => fake()->optional()->dateTime(),
        ];
    }
}