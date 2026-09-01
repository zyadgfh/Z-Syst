<?php

namespace Tests\Unit\Requests;

use App\Models\InsurancePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreInsuranceClaimRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules = [
        'insurance_policy_id' => 'required|exists:insurance_policies,id',
        'total_amount' => 'required|numeric|min:0',
    ];

    public function test_valid_data_passes_validation(): void
    {
        $policy = InsurancePolicy::factory()->create();

        $validator = Validator::make([
            'insurance_policy_id' => $policy->id,
            'total_amount' => 100,
        ], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_missing_insurance_policy_id_fails_validation(): void
    {
        $validator = Validator::make([
            'total_amount' => 100,
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }

    public function test_negative_total_amount_fails_validation(): void
    {
        $validator = Validator::make([
            'insurance_policy_id' => 1,
            'total_amount' => -50,
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }
}
