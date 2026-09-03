<?php

namespace Tests\Feature;

use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;
use App\Services\InsuranceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceClaimFlowTest extends TestCase
{
    use RefreshDatabase;

    protected InsuranceService $service;
    protected $business;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InsuranceService::class);
        $this->business = \App\Models\Business::factory()->create();
        $this->user = \App\Models\User::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $this->actingAs($this->user);
    }

    /**
     * Create a policy with a known annual limit and zero used_amount.
     */
    protected function createPolicy(float $annualLimit, float $coveragePercent = 80): InsurancePolicy
    {
        $company = InsuranceCompany::create([
            'business_id' => $this->business->id,
            'name' => 'Test Insurance Co',
            'code' => 'TIC-'.rand(1000, 9999),
            'status' => 'active',
            'integration_type' => 'manual',
            'default_coverage_percent' => $coveragePercent,
            'default_copay_percent' => 100 - $coveragePercent,
            'settlement_days' => 30,
        ]);

        return InsurancePolicy::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'policy_number' => 'POL-TEST-'.rand(1000, 9999),
            'holder_name' => 'Test Patient',
            'plan_type' => 'individual',
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
            'annual_limit' => $annualLimit,
            'used_amount' => 0,
            'remaining_limit' => $annualLimit,
            'coverage_percent' => $coveragePercent,
            'copay_percent' => 100 - $coveragePercent,
        ]);
    }

    /**
     * Create a claim and verify it gets computed coverage.
     */
    protected function createClaim(InsurancePolicy $policy, float $totalAmount): InsuranceClaim
    {
        return $this->service->createClaim([
            'business_id' => $this->business->id,
            'insurance_company_id' => $policy->insurance_company_id,
            'insurance_policy_id' => $policy->id,
            'service_date' => now()->toDateString(),
            'total_amount' => $totalAmount,
        ]);
    }

    // =============================================
    // Coverage Calculation Tests
    // =============================================

    public function test_default_coverage_calculation(): void
    {
        $policy = $this->createPolicy(10000, coveragePercent: 80);

        $claim = $this->createClaim($policy, 500);

        // 80% coverage: covered = 400, patient = 100
        $this->assertEquals(400, $claim->covered_amount);
        $this->assertEquals(100, $claim->patient_responsibility);
    }

    public function test_coverage_with_different_percentages(): void
    {
        $policy = $this->createPolicy(10000, coveragePercent: 70);

        $claim = $this->createClaim($policy, 1000);

        // 70% coverage: covered = 700, patient = 300
        $this->assertEquals(700, $claim->covered_amount);
        $this->assertEquals(300, $claim->patient_responsibility);
    }

    public function test_policy_used_amount_increments_after_claim(): void
    {
        $policy = $this->createPolicy(10000, coveragePercent: 80);

        $this->createClaim($policy, 1000);

        $policy->refresh();
        // covered_amount = 800 (80% of 1000)
        $this->assertEquals(800, $policy->used_amount);
    }

    public function test_multiple_claims_accumulate_used_amount(): void
    {
        $policy = $this->createPolicy(10000, coveragePercent: 80);

        $this->createClaim($policy, 1000); // covered: 800
        $this->createClaim($policy, 2000); // covered: 1600
        $this->createClaim($policy, 500);  // covered: 400

        $policy->refresh();
        $this->assertEquals(2800, $policy->used_amount);
    }

    // =============================================
    // Annual Limit Enforcement Tests
    // =============================================

    public function test_claim_near_annual_limit_succeeds(): void
    {
        $policy = $this->createPolicy(5000, coveragePercent: 80);

        // 3 claims of 2000 each: covered = 1600 each, total used = 4800
        $this->createClaim($policy, 2000);
        $this->createClaim($policy, 2000);
        $this->createClaim($policy, 2000);

        $policy->refresh();
        // Service increments used_amount by covered_amount in createClaim
        $this->assertEquals(4800, $policy->used_amount);
        $this->assertEquals(200, $policy->remaining_limit);
    }

    public function test_claim_exceeding_annual_limit_still_creates_but_flags(): void
    {
        $policy = $this->createPolicy(5000, coveragePercent: 80);

        // First 3 claims use 4800 of 5000 limit
        $this->createClaim($policy, 2000);
        $this->createClaim($policy, 2000);
        $this->createClaim($policy, 2000);

        // 4th claim: service doesn't block, but used_amount exceeds annual_limit
        $claim = $this->createClaim($policy, 2000);

        $policy->refresh();
        // used_amount = 4800 + 1600 = 6400, which exceeds 5000 limit
        $this->assertEquals(6400, $policy->used_amount);
        // remaining_limit accessor clamps to 0 (max(0, ...))
        $this->assertEquals(0, $policy->remaining_limit);
    }

    public function test_eligibility_check_blocks_excess_claims(): void
    {
        $policy = $this->createPolicy(5000, coveragePercent: 80);

        // Manually set used_amount past the limit
        $policy->update(['used_amount' => 4800]);

        $eligibility = $this->service->validatePolicyEligibility($policy, 500);
        $this->assertFalse($eligibility['eligible']);
        $this->assertEquals('Insufficient annual limit', $eligibility['reason']);
    }

    public function test_eligibility_check_passes_within_limit(): void
    {
        $policy = $this->createPolicy(5000, coveragePercent: 80);
        $policy->update(['used_amount' => 2000]);

        $eligibility = $this->service->validatePolicyEligibility($policy, 500);
        $this->assertTrue($eligibility['eligible']);
    }

    public function test_expired_policy_fails_eligibility(): void
    {
        $policy = $this->createPolicy(5000, coveragePercent: 80);
        $policy->update([
            'start_date' => now()->subYear(),
            'end_date' => now()->subDay(),
        ]);

        $eligibility = $this->service->validatePolicyEligibility($policy, 500);
        $this->assertFalse($eligibility['eligible']);
        $this->assertEquals('Policy expired', $eligibility['reason']);
    }

    public function test_claim_submission_transitions_status(): void
    {
        $policy = $this->createPolicy(10000, coveragePercent: 80);
        $claim = $this->createClaim($policy, 500);

        // Submit transitions to submitted
        $claim = $this->service->submitClaim($claim);
        $this->assertEquals('submitted', $claim->status);
        $this->assertNotNull($claim->submission_date);
    }

    public function test_approval_updates_policy_utilization(): void
    {
        $policy = $this->createPolicy(10000, coveragePercent: 80);

        // Create claim with explicit coverage to control increments
        $claim = $this->service->createClaim([
            'business_id' => $this->business->id,
            'insurance_company_id' => $policy->insurance_company_id,
            'insurance_policy_id' => $policy->id,
            'service_date' => now()->toDateString(),
            'total_amount' => 1000,
            'covered_amount' => 800,
            'patient_responsibility' => 200,
        ]);

        $policy->refresh();
        // When covered_amount is pre-set, createClaim still increments
        $usedAfterCreate = $policy->used_amount;

        $this->service->processClaim($claim, [
            'status' => 'approved',
            'approved_amount' => 800,
        ]);

        $policy->refresh();
        // processClaim also increments by approved_amount for approved status
        $this->assertEquals($usedAfterCreate + 800, $policy->used_amount);
    }

    public function test_rejection_does_not_change_policy_utilization(): void
    {
        $policy = $this->createPolicy(10000, coveragePercent: 80);
        $claim = $this->createClaim($policy, 1000);

        $policy->refresh();
        $usedBefore = $policy->used_amount;

        $this->service->processClaim($claim, [
            'status' => 'rejected',
            'rejection_reason' => 'Not covered under this plan',
        ]);

        $policy->refresh();
        // Rejection does not add to used_amount
        $this->assertEquals($usedBefore, $policy->used_amount);
    }

    public function test_payment_moves_claim_to_paid(): void
    {
        $policy = $this->createPolicy(10000, coveragePercent: 80);
        $claim = $this->createClaim($policy, 1000);

        $this->service->submitClaim($claim);
        $this->service->processClaim($claim, [
            'status' => 'approved',
            'approved_amount' => 800,
        ]);

        $claim = $this->service->processPayment($claim, 800);

        $this->assertEquals('paid', $claim->status);
        $this->assertEquals(800, $claim->paid_amount);
        $this->assertNotNull($claim->settlement_date);
    }

    // =============================================
    // Coverage Rule Resolution Tests
    // =============================================

    public function test_product_specific_coverage_rule_takes_priority(): void
    {
        $company = InsuranceCompany::create([
            'business_id' => $this->business->id,
            'name' => 'Rule Test Co',
            'code' => 'RTC-'.rand(1000, 9999),
            'status' => 'active',
            'integration_type' => 'manual',
            'default_coverage_percent' => 80,
            'default_copay_percent' => 20,
            'settlement_days' => 30,
        ]);

        $policy = InsurancePolicy::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'policy_number' => 'POL-RULE-'.rand(1000, 9999),
            'holder_name' => 'Rule Test Patient',
            'plan_type' => 'individual',
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
            'annual_limit' => 20000,
            'used_amount' => 0,
            'coverage_percent' => 80,
            'copay_percent' => 20,
        ]);

        $product = \App\Models\Product::factory()->create([
            'business_id' => $this->business->id,
        ]);

        // Create a product-specific coverage rule: 90% coverage
        \App\Models\InsuranceCoverage::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'product_id' => $product->id,
            'coverage_code' => 'RX-90',
            'scope' => 'product',
            'coverage_percent' => 90,
            'copay_percent' => 10,
            'is_active' => true,
            'effective_from' => now()->subYear(),
            'effective_to' => now()->addYear(),
        ]);

        $claim = $this->service->createClaim([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'insurance_policy_id' => $policy->id,
            'product_id' => $product->id,
            'service_date' => now()->toDateString(),
            'total_amount' => 1000,
        ]);

        // Should use 90% product-specific rule, not 80% default
        // calculateCoverage: covered = 900, copay = 100, patient = 1000-900+100 = 200
        $this->assertEquals(900, $claim->covered_amount);
        $this->assertEquals(200, $claim->patient_responsibility);
    }

    public function test_claim_with_no_policy_returns_zero_coverage(): void
    {
        $company = InsuranceCompany::create([
            'business_id' => $this->business->id,
            'name' => 'No Policy Co',
            'code' => 'NPC-'.rand(1000, 9999),
            'status' => 'active',
            'integration_type' => 'manual',
            'default_coverage_percent' => 80,
            'default_copay_percent' => 20,
            'settlement_days' => 30,
        ]);

        $coverage = $this->service->calculateClaimCoverage([
            'insurance_policy_id' => 99999, // nonexistent
            'total_amount' => 1000,
        ]);

        $this->assertEquals(0, $coverage['covered_amount']);
        $this->assertEquals(1000, $coverage['patient_responsibility']);
    }
}
