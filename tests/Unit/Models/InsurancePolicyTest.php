<?php

namespace Tests\Unit\Models;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsurancePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create(['business_category_id' => $category->id]);
    }

    public function test_can_create_insurance_policy(): void
    {
        $company = InsuranceCompany::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $policy = InsurancePolicy::factory()->create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'policy_number' => 'POL-001',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('insurance_policies', [
            'policy_number' => 'POL-001',
            'status' => 'active',
        ]);
    }

    public function test_is_expired(): void
    {
        $company = InsuranceCompany::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $expiredPolicy = InsurancePolicy::factory()->create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'end_date' => now()->subDays(1),
        ]);

        $activePolicy = InsurancePolicy::factory()->create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'end_date' => now()->addDays(30),
        ]);

        $this->assertTrue($expiredPolicy->isExpired());
        $this->assertFalse($activePolicy->isExpired());
    }

    public function test_get_remaining_limit(): void
    {
        $company = InsuranceCompany::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $policy = InsurancePolicy::factory()->create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'annual_limit' => 10000,
            'used_amount' => 3000,
        ]);

        $this->assertEquals(7000, $policy->getRemainingLimitAttribute());
    }

    public function test_has_sufficient_limit(): void
    {
        $company = InsuranceCompany::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $policy = InsurancePolicy::factory()->create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'annual_limit' => 10000,
            'used_amount' => 3000,
        ]);

        $this->assertTrue($policy->hasSufficientLimit(5000));
        $this->assertFalse($policy->hasSufficientLimit(8000));
    }

    public function test_scope_active(): void
    {
        $company = InsuranceCompany::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $active = InsurancePolicy::factory()->create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'status' => 'active',
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(30),
        ]);

        $expired = InsurancePolicy::factory()->create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'status' => 'active',
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(1),
        ]);

        $inactive = InsurancePolicy::factory()->create([
            'business_id' => $this->business->id,
            'insurance_company_id' => $company->id,
            'status' => 'expired',
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(30),
        ]);

        $activeCount = InsurancePolicy::active()->count();

        $this->assertEquals(1, $activeCount);
    }

    public function test_scope_for_business(): void
    {
        $business1 = Business::factory()->create(['business_category_id' => BusinessCategory::factory()->create()->id]);
        $business2 = Business::factory()->create(['business_category_id' => BusinessCategory::factory()->create()->id]);

        $company1 = InsuranceCompany::factory()->create(['business_id' => $business1->id]);
        $company2 = InsuranceCompany::factory()->create(['business_id' => $business2->id]);

        InsurancePolicy::factory()->create(['business_id' => $business1->id, 'insurance_company_id' => $company1->id]);
        InsurancePolicy::factory()->create(['business_id' => $business1->id, 'insurance_company_id' => $company1->id]);
        InsurancePolicy::factory()->create(['business_id' => $business2->id, 'insurance_company_id' => $company2->id]);

        $count = InsurancePolicy::forBusiness($business1->id)->count();

        $this->assertEquals(2, $count);
    }
}
