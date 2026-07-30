<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Models\InsurancePolicy;
use App\Models\Party;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Services\InsuranceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Business $business;
    private InsuranceCompany $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->company = InsuranceCompany::create([
            'business_id' => $this->business->id,
            'name' => 'Test Insurer',
            'code' => 'TST-001',
            'status' => 'active',
            'default_coverage_percent' => 80.00,
            'default_copay_percent' => 20.00,
        ]);
    }

    private function actingAsBusinessUser(): self
    {
        return $this->actingAs($this->user);
    }

    // ─── Companies ───────────────────────────────────────────────────────────

    public function test_can_list_companies(): void
    {
        InsuranceCompany::factory()->count(3)->create(['business_id' => $this->business->id]);

        $response = $this->actingAsBusinessUser()
            ->getJson('/api/v1/insurance/companies');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'companies' => [
                    'data' => [
                        '*' => ['id', 'name', 'code', 'status', 'policies_count', 'claims_count'],
                    ],
                ],
                'meta',
            ]);
    }

    public function test_can_create_company(): void
    {
        $payload = [
            'name' => 'New Insurer',
            'code' => 'NEW-002',
            'phone' => '+123456789',
            'email' => 'insurer@example.com',
            'default_coverage_percent' => 75.5,
        ];

        $response = $this->actingAsBusinessUser()
            ->postJson('/api/v1/insurance/companies', $payload);

        $response->assertCreated()
            ->assertJsonPath('company.name', 'New Insurer')
            ->assertJsonPath('company.code', 'NEW-002');

        $this->assertDatabaseHas('insurance_companies', [
            'business_id' => $this->business->id,
            'code' => 'NEW-002',
        ]);
    }

    public function test_company_code_must_be_unique(): void
    {
        InsuranceCompany::create([
            'business_id' => $this->business->id,
            'name' => 'Existing',
            'code' => 'DUP-001',
        ]);

        $response = $this->actingAsBusinessUser()
            ->postJson('/api/v1/insurance/companies', [
                'name' => 'Other',
                'code' => 'DUP-001',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.code.0', fn ($msg) => str_contains((string) $msg, 'taken'));
    }

    public function test_cannot_access_company_from_other_business(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherCompany = InsuranceCompany::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Foreign',
            'code' => 'FRN-001',
        ]);

        $response = $this->actingAsBusinessUser()
            ->getJson("/api/v1/insurance/companies/{$otherCompany->id}");

        $response->assertForbidden();
    }

    // ─── Policies ────────────────────────────────────────────────────────────

    public function test_can_create_policy_with_generated_number(): void
    {
        $payload = [
            'insurance_company_id' => $this->company->id,
            'holder_name' => 'John Doe',
            'holder_dob' => '1985-04-12',
            'holder_gender' => 'male',
            'plan_type' => 'individual',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'annual_limit' => 10000,
        ];

        $response = $this->actingAsBusinessUser()
            ->postJson('/api/v1/insurance/policies', $payload);

        $response->assertCreated()
            ->assertJsonPath('policy.holder_name', 'John Doe')
            ->assertJsonPath('policy.policy_number', fn ($v) => str_starts_with($v, 'POL-'));
    }

    public function test_policy_is_valid_within_date_range(): void
    {
        $policy = InsurancePolicy::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $this->company->id,
            'policy_number' => 'POL-VALID-1',
            'holder_name' => 'Jane',
            'plan_type' => 'individual',
            'status' => 'active',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
            'annual_limit' => 5000,
        ]);

        $this->assertTrue($policy->isValid());
        $this->assertFalse($policy->isValid(now()->addYear()));
    }

    public function test_policy_end_date_must_be_after_start_date(): void
    {
        $response = $this->actingAsBusinessUser()
            ->postJson('/api/v1/insurance/policies', [
                'insurance_company_id' => $this->company->id,
                'holder_name' => 'Bad',
                'start_date' => '2026-12-31',
                'end_date' => '2026-01-01',
            ]);

        $response->assertStatus(422);
    }

    // ─── Claims ──────────────────────────────────────────────────────────────

    public function test_can_create_claim_with_computed_coverage(): void
    {
        $policy = $this->makePolicy(annualLimit: 10000, coverage: 80);

        $response = $this->actingAsBusinessUser()
            ->postJson('/api/v1/insurance/claims', [
                'insurance_policy_id' => $policy->id,
                'service_date' => now()->toDateString(),
                'total_amount' => 1000,
            ]);

        $response->assertCreated()
            ->assertJsonPath('claim.covered_amount', '800.00')
            ->assertJsonPath('claim.patient_responsibility', '200.00')
            ->assertJsonPath('claim.status', 'draft');
    }

    public function test_can_submit_claim_and_transitions_status(): void
    {
        $policy = $this->makePolicy();
        $claim = InsuranceClaim::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $this->company->id,
            'insurance_policy_id' => $policy->id,
            'claim_number' => 'CLM-TEST-1',
            'service_date' => now()->toDateString(),
            'total_amount' => 500,
            'covered_amount' => 400,
            'patient_responsibility' => 100,
            'status' => 'draft',
        ]);

        $response = $this->actingAsBusinessUser()
            ->postJson("/api/v1/insurance/claims/{$claim->id}/submit");

        $response->assertOk()
            ->assertJsonPath('claim.status', 'submitted');
    }

    public function test_approval_updates_policy_utilization(): void
    {
        $policy = $this->makePolicy(annualLimit: 1000, coverage: 100);
        $claim = InsuranceClaim::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $this->company->id,
            'insurance_policy_id' => $policy->id,
            'claim_number' => 'CLM-TEST-2',
            'service_date' => now()->toDateString(),
            'total_amount' => 200,
            'covered_amount' => 200,
            'patient_responsibility' => 0,
            'status' => 'submitted',
            'submission_date' => now()->toDateString(),
        ]);

        $response = $this->actingAsBusinessUser()
            ->postJson("/api/v1/insurance/claims/{$claim->id}/approve", [
                'approved_amount' => 200,
                'external_reference' => 'INS-REF-001',
            ]);

        $response->assertOk()->assertJsonPath('claim.status', 'approved');

        $policy->refresh();
        $this->assertEquals(200.0, (float) $policy->used_amount);
        $this->assertEquals(800.0, (float) $policy->remaining_limit);
    }

    public function test_payment_moves_claim_to_paid(): void
    {
        $policy = $this->makePolicy();
        $claim = InsuranceClaim::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $this->company->id,
            'insurance_policy_id' => $policy->id,
            'claim_number' => 'CLM-PAY-1',
            'service_date' => now()->toDateString(),
            'total_amount' => 100,
            'covered_amount' => 80,
            'patient_responsibility' => 20,
            'status' => 'approved',
        ]);

        $response = $this->actingAsBusinessUser()
            ->postJson("/api/v1/insurance/claims/{$claim->id}/pay", [
                'paid_amount' => 80,
            ]);

        $response->assertOk()
            ->assertJsonPath('claim.status', 'paid')
            ->assertJsonPath('claim.paid_amount', '80.00');
    }

    public function test_rejection_requires_reason(): void
    {
        $policy = $this->makePolicy();
        $claim = InsuranceClaim::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $this->company->id,
            'insurance_policy_id' => $policy->id,
            'claim_number' => 'CLM-REJ-1',
            'service_date' => now()->toDateString(),
            'total_amount' => 100,
            'covered_amount' => 80,
            'patient_responsibility' => 20,
            'status' => 'submitted',
            'submission_date' => now()->toDateString(),
        ]);

        $response = $this->actingAsBusinessUser()
            ->postJson("/api/v1/insurance/claims/{$claim->id}/reject", []);

        $response->assertStatus(422)
            ->assertJsonPath('errors.rejection_reason.0', fn ($v) => str_contains((string) $v, 'required'));
    }

    // ─── Service layer ───────────────────────────────────────────────────────

    public function test_service_resolves_coverage_using_product_rule(): void
    {
        $policy = $this->makePolicy(coverage: 50);
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        InsuranceCoverage::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $this->company->id,
            'product_id' => $product->id,
            'scope' => 'product',
            'coverage_percent' => 95,
            'is_active' => true,
        ]);

        $service = app(InsuranceService::class);
        $resolved = $service->resolveCoverage($policy, 100.0, productId: $product->id);

        $this->assertEquals(95.0, $resolved);
    }

    public function test_summary_endpoint_returns_aggregates(): void
    {
        InsuranceCompany::factory()->count(2)->create(['business_id' => $this->business->id]);
        $policy = $this->makePolicy();
        InsuranceClaim::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $this->company->id,
            'insurance_policy_id' => $policy->id,
            'claim_number' => 'CLM-SUM-1',
            'service_date' => now()->toDateString(),
            'total_amount' => 100,
            'covered_amount' => 80,
            'patient_responsibility' => 20,
            'status' => 'paid',
            'paid_amount' => 80,
        ]);

        $response = $this->actingAsBusinessUser()
            ->getJson('/api/v1/insurance/summary');

        $response->assertOk()
            ->assertJsonPath('data.companies.total', 3)
            ->assertJsonPath('data.policies.total', 1)
            ->assertJsonPath('data.claims.paid_amount', 80.0);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makePolicy(int $annualLimit = 10000, ?float $coverage = null): InsurancePolicy
    {
        return InsurancePolicy::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $this->company->id,
            'policy_number' => 'POL-' . uniqid(),
            'holder_name' => 'Test Holder',
            'plan_type' => 'individual',
            'status' => 'active',
            'start_date' => now()->subDays(1),
            'end_date' => now()->addYear(),
            'annual_limit' => $annualLimit,
            'used_amount' => 0,
            'coverage_percent' => $coverage,
        ]);
    }
}
