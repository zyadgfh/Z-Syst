<?php

namespace Tests\Unit\Models;

use App\Models\InsuranceCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('accounting')]
class InsuranceCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_insurance_company(): void
    {
        $company = InsuranceCompany::factory()->create([
            'name' => 'Test Insurance Co',
            'code' => 'TIC001',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('insurance_companies', [
            'name' => 'Test Insurance Co',
            'code' => 'TIC001',
            'status' => 'active',
        ]);
    }

    public function test_calculate_default_coverage(): void
    {
        $company = InsuranceCompany::factory()->create([
            'default_coverage_percent' => 80.00,
            'default_copay_percent' => 20.00,
        ]);

        $coverage = $company->calculateDefaultCoverage(1000);

        $this->assertEquals(800.00, $coverage['covered_amount']);
        $this->assertEquals(200.00, $coverage['patient_responsibility']); // 1000 - 800
        $this->assertEquals(80.00, $coverage['coverage_percent']);
        $this->assertEquals(20.00, $coverage['copay_percent']);
    }

    public function test_scope_active(): void
    {
        InsuranceCompany::factory()->create(['status' => 'active']);
        InsuranceCompany::factory()->create(['status' => 'inactive']);
        InsuranceCompany::factory()->create(['status' => 'suspended']);

        $active = InsuranceCompany::active()->count();

        $this->assertEquals(1, $active);
    }

    public function test_scope_for_business(): void
    {
        $business1 = \App\Models\Business::factory()->create();
        $business2 = \App\Models\Business::factory()->create();

        InsuranceCompany::factory()->create(['business_id' => $business1->id]);
        InsuranceCompany::factory()->create(['business_id' => $business1->id]);
        InsuranceCompany::factory()->create(['business_id' => $business2->id]);

        $count = InsuranceCompany::forBusiness($business1->id)->count();

        $this->assertEquals(2, $count);
    }

    public function test_api_credentials_encrypted(): void
    {
        $company = InsuranceCompany::factory()->create([
            'api_credentials' => ['api_key' => 'test-key-123', 'api_secret' => 'test-secret-456'],
        ]);

        // Reload from DB to verify encryption roundtrip
        $fresh = $company->fresh();

        $this->assertEquals(
            ['api_key' => 'test-key-123', 'api_secret' => 'test-secret-456'],
            $fresh->api_credentials
        );

        // Raw DB value should be encrypted (not plaintext JSON)
        $raw = \DB::table('insurance_companies')->where('id', $company->id)->value('api_credentials');
        $this->assertStringNotContainsString('test-key-123', $raw);
    }
}