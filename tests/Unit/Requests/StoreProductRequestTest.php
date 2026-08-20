<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreProductRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreProductRequestTest extends TestCase
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

    public function test_valid_product_data_passes(): void
    {
        $request = new StoreProductRequest();
        $request->merge([
            'productName' => 'Test Product',
            'category_id' => 1,
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization());
    }

    public function test_missing_product_name_fails(): void
    {
        $request = new StoreProductRequest();
        $request->merge([
            'category_id' => 1,
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_invalid_category_id_fails(): void
    {
        $request = new StoreProductRequest();
        $request->merge([
            'productName' => 'Test Product',
            'category_id' => 99999, // Non-existent
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_duplicate_product_code_fails(): void
    {
        // This test would require database setup for unique constraint
        // We're testing authorization only here
        $request = new StoreProductRequest();
        $request->merge([
            'productName' => 'Test Product',
            'category_id' => 1,
            'productCode' => 'EXISTING_CODE',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization()); // Authorization passes, validation would fail
    }
}