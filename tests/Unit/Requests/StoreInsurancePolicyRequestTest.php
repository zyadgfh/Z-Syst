<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreInsurancePolicyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreInsurancePolicyRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'business_id' => 1,
        ]);
        
        $this->actingAs($this->user);
    }

    public function test_valid_policy_data_passes(): void
    {
        $request = new StoreInsurancePolicyRequest();
        $request->merge([
            'insurance_company_id' => 1,
            'customer_id' => 1,
            'policy_number' => 'POL-001',
            'holder_name' => 'John Doe',
            'holder_dob' => '1990-01-01',
            'holder_gender' => 'male',
            'holder_phone' => '0123456789',
            'holder_email' => 'john@example.com',
            'plan_type' => 'basic',
            'status' => 'active',
            'start_date' => '2024-01-01',
            'end_date' => '2025-12-31',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization());
    }

    public function test_missing_insurance_company_id_fails(): void
    {
        $request = new StoreInsurancePolicyRequest();
        $request->merge([
            'customer_id' => 1,
            'policy_number' => 'POL-001',
            'holder_name' => 'John Doe',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }
}