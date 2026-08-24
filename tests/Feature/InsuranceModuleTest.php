<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;
use App\Services\InsuranceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected InsuranceService $insuranceService;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->insuranceService = app(InsuranceService::class);
        $this->business = Business::factory()->create();
    }

    /**
     * Test creating insurance company
     */
    public function test_can_create_insurance_company()
    {
        $data = [
            'business_id' => $this->business->id,
            'name' => 'Test Insurance Company',
            'contact_person' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'test@insurance.com',
            'status' => 'active',
            'integration_type' => 'manual',
            'default_coverage_percent' => 80,
            'default_copay_percent' => 20,
            'settlement_days' => 30,
        ];

        $company = $this->insuranceService->createCompany($data);

        $this->assertInstanceOf(InsuranceCompany::class, $company);
        $this->assertEquals('Test Insurance Company', $company->name);
        $this->assertNotNull($company->code);
        $this->assertStringStartsWith('INS-', $company->code);
    }

    /**
     * Test creating insurance policy
     */
    public function test_can_create_insurance_policy()
    {
        $company = InsuranceCompany::factory()->create();

        $data = [
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'holder_name' => 'Jane Doe',
            'plan_type' => 'individual',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'annual_limit' => 10000,
        ];

        $policy = $this->insuranceService->createPolicy($data);

        $this->assertInstanceOf(InsurancePolicy::class, $policy);
        $this->assertEquals('Jane Doe', $policy->holder_name);
        $this->assertNotNull($policy->policy_number);
        $this->assertStringStartsWith('POL-', $policy->policy_number);
        $this->assertEquals(10000, $policy->remaining_limit);
    }

    /**
     * Test creating insurance claim
     */
    public function test_can_create_insurance_claim()
    {
        $company = InsuranceCompany::factory()->create([
            'default_coverage_percent' => 80,
            'default_copay_percent' => 20,
        ]);
        $policy = InsurancePolicy::factory()->create([
            'insurance_company_id' => $company->id,
            'annual_limit' => 5000,
            'coverage_percent' => 80,
        ]);

        $data = [
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'insurance_policy_id' => $policy->id,
            'service_date' => now(),
            'total_amount' => 100,
        ];

        $claim = $this->insuranceService->createClaim($data);

        $this->assertInstanceOf(InsuranceClaim::class, $claim);
        $this->assertNotNull($claim->claim_number);
        $this->assertStringStartsWith('CLM-', $claim->claim_number);
        $this->assertEquals(80, $claim->covered_amount); // 80% of 100
        $this->assertEquals(20, $claim->patient_responsibility); // 20% of 100
    }

    /**
     * Test policy eligibility validation
     */
    public function test_policy_eligibility_validation()
    {
        $expiredPolicy = InsurancePolicy::factory()->create([
            'end_date' => now()->subDay(),
            'annual_limit' => 1000,
            'used_amount' => 0,
        ]);

        $activePolicy = InsurancePolicy::factory()->create([
            'end_date' => now()->addYear(),
            'annual_limit' => 1000,
            'used_amount' => 0,
        ]);

        $expiredResult = $this->insuranceService->validatePolicyEligibility($expiredPolicy, 500);
        $activeResult = $this->insuranceService->validatePolicyEligibility($activePolicy, 500);

        $this->assertFalse($expiredResult['eligible']);
        $this->assertEquals('Policy expired', $expiredResult['reason']);

        $this->assertTrue($activeResult['eligible']);
        $this->assertNull($activeResult['reason']);
    }

    /**
     * Test claim submission
     */
    public function test_can_submit_claim()
    {
        $claim = InsuranceClaim::factory()->create(['status' => 'draft']);

        $submittedClaim = $this->insuranceService->submitClaim($claim);

        $this->assertEquals('submitted', $submittedClaim->status);
        $this->assertNotNull($submittedClaim->submission_date);
    }

    /**
     * Test claim processing
     */
    public function test_can_process_claim()
    {
        $claim = InsuranceClaim::factory()->create([
            'status' => 'submitted',
            'total_amount' => 100,
            'covered_amount' => 80,
        ]);

        $processData = [
            'status' => 'approved',
            'approved_amount' => 80,
            'rejected_amount' => 0,
        ];

        $processedClaim = $this->insuranceService->processClaim($claim, $processData);

        $this->assertEquals('approved', $processedClaim->status);
        $this->assertEquals(80, $processedClaim->approved_amount);
    }

    /**
     * Test claim payment processing
     */
    public function test_can_process_claim_payment()
    {
        $claim = InsuranceClaim::factory()->create([
            'status' => 'approved',
            'approved_amount' => 80,
            'paid_amount' => 0,
        ]);

        $paidClaim = $this->insuranceService->processPayment($claim, 80);

        $this->assertEquals('paid', $paidClaim->status);
        $this->assertEquals(80, $paidClaim->paid_amount);
        $this->assertNotNull($paidClaim->settlement_date);
    }

    /**
     * Test claim statistics
     */
    public function test_can_get_claim_statistics()
    {
        $businessId = 1;

        InsuranceClaim::factory()->count(5)->create([
            'business_id' => $businessId,
            'status' => 'approved',
            'total_amount' => 100,
            'covered_amount' => 80,
            'paid_amount' => 80,
        ]);

        InsuranceClaim::factory()->count(3)->create([
            'business_id' => $businessId,
            'status' => 'rejected',
            'total_amount' => 50,
            'covered_amount' => 0,
            'paid_amount' => 0,
        ]);

        $statistics = $this->insuranceService->getClaimStatistics($businessId);

        $this->assertEquals(8, $statistics['total_claims']);
        $this->assertEquals(650, $statistics['total_amount']); // 5*100 + 3*50
        $this->assertEquals(400, $statistics['total_covered']); // 5*80
        $this->assertEquals(400, $statistics['total_paid']); // 5*80
        $this->assertEquals(5, $statistics['approved_claims']);
        $this->assertEquals(3, $statistics['rejected_claims']);
    }

    /**
     * Test unique code generation
     */
    public function test_generates_unique_company_codes()
    {
        $company1 = InsuranceCompany::factory()->create();
        $company2 = InsuranceCompany::factory()->create();

        $this->assertNotEquals($company1->code, $company2->code);
        $this->assertStringStartsWith('INS-', $company1->code);
        $this->assertStringStartsWith('INS-', $company2->code);
    }

    /**
     * Test unique policy number generation
     */
    public function test_generates_unique_policy_numbers()
    {
        $policy1 = InsurancePolicy::factory()->create();
        $policy2 = InsurancePolicy::factory()->create();

        $this->assertNotEquals($policy1->policy_number, $policy2->policy_number);
        $this->assertStringStartsWith('POL-', $policy1->policy_number);
        $this->assertStringStartsWith('POL-', $policy2->policy_number);
    }

    /**
     * Test unique claim number generation
     */
    public function test_generates_unique_claim_numbers()
    {
        $claim1 = InsuranceClaim::factory()->create();
        $claim2 = InsuranceClaim::factory()->create();

        $this->assertNotEquals($claim1->claim_number, $claim2->claim_number);
        $this->assertStringStartsWith('CLM-', $claim1->claim_number);
        $this->assertStringStartsWith('CLM-', $claim2->claim_number);
    }
}
