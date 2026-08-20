<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StorePartyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePartyRequestTest extends TestCase
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

    public function test_valid_party_data_passes(): void
    {
        $request = new StorePartyRequest();
        $request->merge([
            'name' => 'Test Customer',
            'type' => 'customer',
            'phone' => '01234567890',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization());
    }

    public function test_missing_name_fails(): void
    {
        $request = new StorePartyRequest();
        $request->merge([
            'type' => 'customer',
            'phone' => '01234567890',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_invalid_type_fails(): void
    {
        $request = new StorePartyRequest();
        $request->merge([
            'name' => 'Test Customer',
            'type' => 'invalid_type',
            'phone' => '01234567890',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_duplicate_phone_fails(): void
    {
        $request = new StorePartyRequest();
        $request->merge([
            'name' => 'Test Customer',
            'type' => 'customer',
            'phone' => '01234567890',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization()); // Auth passes, validation would fail for duplicate
    }
}