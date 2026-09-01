<?php

namespace Tests\Unit\Models;

use App\Models\InsuranceClaim;
use App\Models\InsurancePolicy;
use App\Models\InsuranceCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_insurance_claim(): void
    {
        $company = InsuranceCompany::factory()->create();
        $policy = InsurancePolicy::factory()->create([
            'insurance_company_id' => $company->id,
            'coverage_percent' => 80,
        ]);

        $claim = InsuranceClaim::factory()->create([
            'insurance_company_id' => $company->id,
            'insurance_policy_id' => $policy->id,
            'claim_number' => 'CLM-001',
            'total_amount' => 1000,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('insurance_claims', [
            'claim_number' => 'CLM-001',
            'total_amount' => 1000,
            'status' => 'draft',
        ]);
    }

    public function test_calculate_coverage(): void
    {
        $company = InsuranceCompany::factory()->create([
            'default_coverage_percent' => 80,
            'default_copay_percent' => 20,
        ]);

        $policy = InsurancePolicy::factory()->create([
            'insurance_company_id' => $company->id,
            'coverage_percent' => 70,
        ]);

        $claim = InsuranceClaim::factory()->create([
            'insurance_policy_id' => $policy->id,
            'total_amount' => 1000,
        ]);

        $coverage = $claim->calculateCoverage();

        // Should use policy coverage_percent (70%)
        $this->assertEquals(700, $coverage['covered_amount']);
        $this->assertEquals(300, $coverage['patient_responsibility']);
        $this->assertEquals(70, $coverage['coverage_percent']);
    }

    public function test_calculate_coverage_falls_back_to_company_default(): void
    {
        $company = InsuranceCompany::factory()->create([
            'default_coverage_percent' => 80,
        ]);

        $policy = InsurancePolicy::factory()->create([
            'insurance_company_id' => $company->id,
            'coverage_percent' => null,
        ]);

        $claim = InsuranceClaim::factory()->create([
            'insurance_policy_id' => $policy->id,
            'total_amount' => 1000,
        ]);

        $coverage = $claim->calculateCoverage();

        // Should fall back to company default (80%)
        $this->assertEquals(800, $coverage['covered_amount']);
        $this->assertEquals(200, $coverage['patient_responsibility']);
        $this->assertEquals(80, $coverage['coverage_percent']);
    }

    public function test_is_submitted(): void
    {
        $draftClaim = InsuranceClaim::factory()->create(['status' => 'draft']);
        $submittedClaim = InsuranceClaim::factory()->create(['status' => 'submitted']);
        $approvedClaim = InsuranceClaim::factory()->create(['status' => 'approved']);
        $paidClaim = InsuranceClaim::factory()->create(['status' => 'paid']);

        $this->assertFalse($draftClaim->isSubmitted());
        $this->assertTrue($submittedClaim->isSubmitted());
        $this->assertTrue($approvedClaim->isSubmitted());
        $this->assertTrue($paidClaim->isSubmitted());
    }

    public function test_is_approved(): void
    {
        $draftClaim = InsuranceClaim::factory()->create(['status' => 'draft']);
        $approvedClaim = InsuranceClaim::factory()->create(['status' => 'approved']);
        $partiallyApproved = InsuranceClaim::factory()->create(['status' => 'partially_approved']);
        $paidClaim = InsuranceClaim::factory()->create(['status' => 'paid']);
        $rejectedClaim = InsuranceClaim::factory()->create(['status' => 'rejected']);

        $this->assertFalse($draftClaim->isApproved());
        $this->assertTrue($approvedClaim->isApproved());
        $this->assertTrue($partiallyApproved->isApproved());
        $this->assertTrue($paidClaim->isApproved());
        $this->assertFalse($rejectedClaim->isApproved());
    }

    public function test_is_paid(): void
    {
        $paidClaim = InsuranceClaim::factory()->create(['status' => 'paid']);
        $approvedClaim = InsuranceClaim::factory()->create(['status' => 'approved']);

        $this->assertTrue($paidClaim->isPaid());
        $this->assertFalse($approvedClaim->isPaid());
    }

    public function test_is_rejected(): void
    {
        $rejectedClaim = InsuranceClaim::factory()->create(['status' => 'rejected']);
        $approvedClaim = InsuranceClaim::factory()->create(['status' => 'approved']);

        $this->assertTrue($rejectedClaim->isRejected());
        $this->assertFalse($approvedClaim->isRejected());
    }

    public function test_scope_pending(): void
    {
        InsuranceClaim::factory()->create(['status' => 'draft']);
        InsuranceClaim::factory()->create(['status' => 'submitted']);
        InsuranceClaim::factory()->create(['status' => 'under_review']);
        InsuranceClaim::factory()->create(['status' => 'approved']);

        $pendingCount = InsuranceClaim::pending()->count();

        $this->assertEquals(3, $pendingCount);
    }

    public function test_scope_approved(): void
    {
        InsuranceClaim::factory()->create(['status' => 'draft']);
        InsuranceClaim::factory()->create(['status' => 'approved']);
        InsuranceClaim::factory()->create(['status' => 'partially_approved']);
        InsuranceClaim::factory()->create(['status' => 'rejected']);

        $approvedCount = InsuranceClaim::approved()->count();

        $this->assertEquals(2, $approvedCount);
    }

    public function test_scope_paid(): void
    {
        InsuranceClaim::factory()->create(['status' => 'paid']);
        InsuranceClaim::factory()->create(['status' => 'approved']);

        $paidCount = InsuranceClaim::paid()->count();

        $this->assertEquals(1, $paidCount);
    }

    public function test_scope_rejected(): void
    {
        InsuranceClaim::factory()->create(['status' => 'rejected']);
        InsuranceClaim::factory()->create(['status' => 'approved']);

        $rejectedCount = InsuranceClaim::rejected()->count();

        $this->assertEquals(1, $rejectedCount);
    }
}