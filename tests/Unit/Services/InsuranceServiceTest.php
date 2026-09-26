<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;
use App\Services\InsuranceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InsuranceService $service;
    private Business $business;
    private InsurancePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InsuranceService::class);
        $this->business = Business::factory()->create();

        $company = InsuranceCompany::factory()->create([
            'business_id' => $this->business->id,
            'default_coverage_percent' => 80,
        ]);

        $this->policy = InsurancePolicy::factory()->create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'coverage_percent' => 80,
            'annual_limit' => 10000,
            'used_amount' => 0,
            'remaining_limit' => 10000,
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
        ]);
    }

    private function claim(string $status = 'submitted'): InsuranceClaim
    {
        return InsuranceClaim::factory()->create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $this->policy->insurance_company_id,
            'insurance_policy_id' => $this->policy->id,
            'total_amount' => 100,
            'covered_amount' => 80,
            'patient_responsibility' => 20,
            'approved_amount' => 0,
            'paid_amount' => 0,
            'rejected_amount' => 0,
            'status' => $status,
            'submission_date' => now(),
        ]);
    }

    public function test_approval_transitions_submitted_claim_and_caps_approved_amount(): void
    {
        $claim = $this->claim();

        $approved = $this->service->recordApproval($claim, 80, 'EXT-1');

        $this->assertSame('approved', $approved->status);
        $this->assertSame('80.00', $approved->approved_amount);
        $this->assertSame('EXT-1', $approved->external_reference);
    }

    public function test_partial_approval_is_recorded(): void
    {
        $claim = $this->claim();

        $approved = $this->service->recordApproval($claim, 50);

        $this->assertSame('partially_approved', $approved->status);
        $this->assertSame('50.00', $approved->approved_amount);
        $this->assertSame('30.00', $approved->rejected_amount);
    }

    public function test_rejection_requires_submitted_state(): void
    {
        $claim = $this->claim('draft');

        $this->expectException(\DomainException::class);

        $this->service->rejectClaim($claim, 'Eligibility failed');
    }

    public function test_payment_cannot_exceed_approved_amount(): void
    {
        $claim = $this->claim('approved');
        $claim->update(['approved_amount' => 60]);

        $this->expectException(\DomainException::class);

        $this->service->recordPayment($claim, 61);
    }

    public function test_full_payment_marks_claim_paid(): void
    {
        $claim = $this->claim('approved');
        $claim->update(['approved_amount' => 60]);

        $paid = $this->service->recordPayment($claim, 60);

        $this->assertSame('paid', $paid->status);
        $this->assertSame('60.00', $paid->paid_amount);
        $this->assertNotNull($paid->settlement_date);
    }
}
