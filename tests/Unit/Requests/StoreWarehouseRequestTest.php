<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreWarehouseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreWarehouseRequestTest extends TestCase
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

    public function test_valid_warehouse_data_passes(): void
    {
        $request = new StoreWarehouseRequest();
        $request->merge([
            'name' => 'Main Warehouse',
            'code' => 'WH001',
            'location' => '123 Main St',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization());
    }

    public function test_missing_name_fails(): void
    {
        $request = new StoreWarehouseRequest();
        $request->merge([
            'code' => 'WH001',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_missing_code_fails(): void
    {
        $request = new StoreWarehouseRequest();
        $request->merge([
            'name' => 'Main Warehouse',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }
}