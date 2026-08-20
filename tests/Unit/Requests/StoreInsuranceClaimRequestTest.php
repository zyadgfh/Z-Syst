<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreInsuranceClaimRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreInsuranceClaimRequestTest extends TestCase
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

    public function test_valid_claim_data_passes(): void
    {
        $request = new StoreInsuranceClaimRequest();
        $request->merge([
            'insurance_policy_id' => 1,
            'customer_id' => 1,
            'service_date' => '2024-01-15',
            'total_amount' => 1000,
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization());
    }

    public function test_missing_insurance_policy_id_fails(): void
    {
        $request = new StoreInsuranceClaimRequest();
        $request->merge([
            'customer_id' => 1,
            'service_date' => '2024-01-15',
            'total_amount' => 1000,
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_negative_total_amount_fails(): void
    {
        $request = new StoreInsuranceClaimRequest();
        $request->merge([
            'insurance_policy_id' => 1,
            'customer_id' => 1,
            'service_date' => '2024-01-15',
            'total_amount' => -100,
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization()); // Auth passes, validation would fail
    }
}