<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceViewRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected InsuranceCompany $company;
    protected InsurancePolicy $policy;
    protected InsuranceClaim $claim;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create([
            'business_category_id' => $category->id,
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'role' => 'shop-owner',
        ]);

        // Seed insurance permissions
        $perms = [
            'insurance-companies-create', 'insurance-companies-read', 'insurance-companies-update', 'insurance-companies-delete',
            'insurance-policies-create', 'insurance-policies-read', 'insurance-policies-update', 'insurance-policies-delete',
            'insurance-claims-create', 'insurance-claims-read', 'insurance-claims-update', 'insurance-claims-delete',
        ];
        foreach ($perms as $p) {
            \Spatie\Permission\Models\Permission::create(['name' => $p, 'guard_name' => 'web']);
        }
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $role = \Spatie\Permission\Models\Role::create(['name' => 'shop-owner', 'guard_name' => 'web']);
        $role->syncPermissions($perms);
        $this->owner->assignRole('shop-owner');
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->owner, 'web');

        // Create test data
        $this->company = InsuranceCompany::create([
            'business_id' => $this->business->id,
            'name' => 'Test Insurance Co',
            'code' => 'TIC-001',
            'status' => 'active',
            'integration_type' => 'manual',
            'default_coverage_percent' => 80,
            'default_copay_percent' => 20,
            'settlement_days' => 30,
        ]);

        $this->policy = InsurancePolicy::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $this->company->id,
            'policy_number' => 'POL-TEST-001',
            'holder_name' => 'Test Patient',
            'plan_type' => 'individual',
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
            'annual_limit' => 10000,
            'used_amount' => 0,
            'coverage_percent' => 80,
            'copay_percent' => 20,
        ]);

        $this->claim = InsuranceClaim::create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $this->company->id,
            'insurance_policy_id' => $this->policy->id,
            'claim_number' => 'CLM-TEST-001',
            'service_date' => now()->toDateString(),
            'total_amount' => 500,
            'covered_amount' => 400,
            'patient_responsibility' => 100,
            'status' => 'draft',
        ]);
    }

    // =============================================
    // Company Views
    // =============================================

    public function test_company_index_renders(): void
    {
        $response = $this->get(route('admin.insurance.companies.index'));
        $response->assertOk();
        $response->assertSee('Test Insurance Co');
    }

    public function test_company_create_form_renders(): void
    {
        $response = $this->get(route('admin.insurance.companies.create'));
        $response->assertOk();
        $response->assertSee('name');
        $response->assertSee('status');
        $response->assertSee('integration_type');
        $response->assertSee('default_coverage_percent');
    }

    public function test_company_edit_form_renders_with_data(): void
    {
        $response = $this->get(route('admin.insurance.companies.edit', $this->company));
        $response->assertOk();
        $response->assertSee('Test Insurance Co');
        $response->assertSee('80'); // default_coverage_percent in input value
    }

    public function test_company_show_renders_with_details(): void
    {
        $response = $this->get(route('admin.insurance.companies.show', $this->company));
        $response->assertOk();
        $response->assertSee('Test Insurance Co');
        $response->assertSee('TIC-001'); // code in <code> tag
        $response->assertSee('80.00%'); // decimal:2 cast renders as 80.00%
        $response->assertSee('Active'); // ucfirst($company->status)
    }

    // =============================================
    // Policy Views
    // =============================================

    public function test_policy_index_renders(): void
    {
        $response = $this->get(route('admin.insurance.policies.index'));
        $response->assertOk();
        $response->assertSee('POL-TEST-001'); // policy number in table
        $response->assertSee('Test Insurance Co'); // company name in table
    }

    public function test_policy_create_form_renders(): void
    {
        $response = $this->get(route('admin.insurance.policies.create'));
        $response->assertOk();
        $response->assertSee('insurance_company_id');
        $response->assertSee('holder_name');
        $response->assertSee('plan_type');
        $response->assertSee('annual_limit');
        // Should show the insurance company in dropdown
        $response->assertSee('Test Insurance Co');
    }

    public function test_policy_edit_form_renders_with_data(): void
    {
        $response = $this->get(route('admin.insurance.policies.edit', $this->policy));
        $response->assertOk();
        $response->assertSee('Test Patient'); // holder_name in input value
        $response->assertSee('Test Insurance Co'); // company name in dropdown
        $response->assertSee('10000'); // annual_limit in input value
    }

    public function test_policy_show_renders_with_details(): void
    {
        $response = $this->get(route('admin.insurance.policies.show', $this->policy));
        $response->assertOk();
        $response->assertSee('POL-TEST-001');
        $response->assertSee('Test Patient');
        $response->assertSee('10,000.00'); // annual_limit formatted with number_format
        $response->assertSee('0.00'); // used_amount formatted
    }

    // =============================================
    // Claim Views
    // =============================================

    public function test_claim_index_renders(): void
    {
        $response = $this->get(route('admin.insurance.claims.index'));
        $response->assertOk();
        $response->assertSee('CLM-TEST-001');
        $response->assertSee('500'); // total_amount
    }

    public function test_claim_create_form_renders(): void
    {
        $response = $this->get(route('admin.insurance.claims.create'));
        $response->assertOk();
        $response->assertSee('insurance_company_id');
        $response->assertSee('insurance_policy_id');
        $response->assertSee('service_date');
        $response->assertSee('total_amount');
        // Should show the insurance company in dropdown
        $response->assertSee('Test Insurance Co');
    }

    public function test_claim_edit_form_renders_with_data(): void
    {
        $response = $this->get(route('admin.insurance.claims.edit', $this->claim));
        $response->assertOk();
        $response->assertSee('CLM-TEST-001');
        $response->assertSee('500'); // total_amount
        $response->assertSee('400'); // covered_amount
    }

    public function test_claim_show_renders_with_financial_summary(): void
    {
        $response = $this->get(route('admin.insurance.claims.show', $this->claim));
        $response->assertOk();
        $response->assertSee('CLM-TEST-001');
        $response->assertSee('500'); // total_amount
        $response->assertSee('400'); // covered_amount
        $response->assertSee('100'); // patient_responsibility
        $response->assertSee('Test Insurance Co');
        $response->assertSee('POL-TEST-001');
    }

    // =============================================
    // Statistics View
    // =============================================

    public function test_statistics_dashboard_renders(): void
    {
        $response = $this->get(route('admin.insurance.claims.statistics.dashboard'));
        $response->assertOk();
        $response->assertSee('totalClaims');
        $response->assertSee('approvedClaims');
        $response->assertSee('pendingClaims');
        $response->assertSee('rejectedClaims');
    }

    public function test_statistics_api_returns_json(): void
    {
        $response = $this->getJson(route('admin.insurance.claims.statistics'));
        $response->assertOk();
        $response->assertJsonStructure([
            'total_claims',
            'total_amount',
            'total_covered',
            'total_paid',
            'pending_claims',
            'approved_claims',
            'rejected_claims',
            'paid_claims',
            'average_processing_days',
        ]);
    }

    // =============================================
    // Navigation & Links
    // =============================================

    public function test_company_index_has_links_to_crud(): void
    {
        $response = $this->get(route('admin.insurance.companies.index'));
        $response->assertOk();
        $response->assertSee(route('admin.insurance.companies.create'));
        $response->assertSee(route('admin.insurance.companies.edit', $this->company));
    }

    public function test_claim_index_has_link_to_statistics(): void
    {
        $response = $this->get(route('admin.insurance.claims.index'));
        $response->assertOk();
        $response->assertSee(route('admin.insurance.claims.statistics.dashboard'));
    }
}
