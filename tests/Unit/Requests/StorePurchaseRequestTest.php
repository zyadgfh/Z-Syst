<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StorePurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePurchaseRequestTest extends TestCase
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

    public function test_valid_purchase_data_passes(): void
    {
        $request = new StorePurchaseRequest();
        $request->merge([
            'products' => [
                [
                    'product_id' => 1,
                    'purchase_without_tax' => 80,
                    'purchase_with_tax' => 92,
                    'profit_percent' => 20,
                    'sales_price' => 100,
                    'wholesale_price' => 95,
                    'quantities' => 10,
                ],
            ],
            'purchaseDate' => now()->format('Y-m-d'),
            'party_id' => 1,
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization());
    }

    public function test_missing_party_id_fails(): void
    {
        $request = new StorePurchaseRequest();
        $request->merge([
            'products' => [
                [
                    'product_id' => 1,
                    'purchase_without_tax' => 80,
                    'purchase_with_tax' => 92,
                    'profit_percent' => 20,
                    'sales_price' => 100,
                    'wholesale_price' => 95,
                    'quantities' => 10,
                ],
            ],
            'purchaseDate' => now()->format('Y-m-d'),
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_empty_products_fails(): void
    {
        $request = new StorePurchaseRequest();
        $request->merge([
            'products' => [],
            'purchaseDate' => now()->format('Y-m-d'),
            'party_id' => 1,
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }
}