<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreInsuranceCompanyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreInsuranceCompanyRequestTest extends TestCase
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

    public function test_valid_company_data_passes(): void
    {
        $request = new StoreInsuranceCompanyRequest();
        $request->merge([
            'name' => 'Test Insurance Co',
            'code' => 'TIC001',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization());
    }

    public function test_missing_name_fails(): void
    {
        $request = new StoreInsuranceCompanyRequest();
        $request->merge([
            'code' => 'TIC001',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_invalid_integration_type_fails(): void
    {
        $request = new StoreInsuranceCompanyRequest();
        $request->merge([
            'name' => 'Test Insurance Co',
            'code' => 'TIC001',
            'integration_type' => 'invalid_type',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_invalid_status_fails(): void
    {
        $request = new StoreInsuranceCompanyRequest();
        $request->merge([
            'name' => 'Test Insurance Co',
            'code' => 'TIC001',
            'status' => 'invalid_status',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }
}