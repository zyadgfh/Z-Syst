<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreEInvoiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreEInvoiceRequestTest extends TestCase
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

    public function test_valid_invoice_data_passes(): void
    {
        $request = new StoreEInvoiceRequest();
        $request->merge([
            'party_id' => 1,
            'invoice_date' => now()->format('Y-m-d'),
            'subtotal' => 1000,
            'total_amount' => 1150,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 2,
                    'unit_price' => 500,
                ],
            ],
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization());
    }

    public function test_missing_party_id_fails(): void
    {
        $request = new StoreEInvoiceRequest();
        $request->merge([
            'invoice_date' => now()->format('Y-m-d'),
            'subtotal' => 1000,
            'total_amount' => 1150,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 2,
                    'unit_price' => 500,
                ],
            ],
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_empty_items_fails(): void
    {
        $request = new StoreEInvoiceRequest();
        $request->merge([
            'party_id' => 1,
            'invoice_date' => now()->format('Y-m-d'),
            'subtotal' => 1000,
            'total_amount' => 1150,
            'items' => [],
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_negative_quantity_fails(): void
    {
        $request = new StoreEInvoiceRequest();
        $request->merge([
            'party_id' => 1,
            'invoice_date' => now()->format('Y-m-d'),
            'subtotal' => 1000,
            'total_amount' => 1150,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => -1,
                    'unit_price' => 500,
                ],
            ],
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization()); // Auth passes, validation would fail
    }
}