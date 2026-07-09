<?php

namespace Database\Factories;

use App\Models\InsurancePlan;
use App\Models\InsuranceCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsurancePlanFactory extends Factory
{
    protected $model = InsurancePlan::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'insurance_company_id' => InsuranceCompany::factory(),
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->bothify('PLAN-####')),
            'coverage_percentage' => fake()->randomFloat(2, 50, 100),
            'max_coverage' => fake()->optional()->randomFloat(2, 10000, 100000),
            'annual_limit' => fake()->optional()->randomFloat(2, 10000, 100000),
            'co_pay' => fake()->randomFloat(2, 0, 50),
            'requires_pre_approval' => fake()->boolean(),
            'covered_items' => fake()->optional()->sentence(),
            'exclusions' => fake()->optional()->sentence(),
            'is_active' => true,
            'created_by' => 1,
        ];
    }
}