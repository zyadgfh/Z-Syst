<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseApiTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $user;
    private Party $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->supplier = Party::factory()->create([
            'business_id' => $this->business->id,
            'type' => 'supplier',
        ]);
    }

    // ── Authentication ──

    public function test_unauthenticated_user_cannot_access_purchases()
    {
        $response = $this->getJson('/api/v1/purchase');
        $response->assertStatus(401);
    }

    // ── Index (List) ──

    public function test_can_list_purchases()
    {
        Purchase::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->supplier->id,
            'invoiceNumber' => 'P-00001',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/purchase');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_list_purchases_is_paginated()
    {
        for ($i = 1; $i <= 12; $i++) {
            Purchase::factory()->create([
                'business_id' => $this->business->id,
                'user_id' => $this->user->id,
                'party_id' => $this->supplier->id,
                'invoiceNumber' => 'P-' . str_pad($i, 5, '0', STR_PAD_LEFT),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/purchase?per_page=5');

        $response->assertStatus(200);
    }

    // ── Show ──

    public function test_can_show_purchase()
    {
        $purchase = Purchase::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->supplier->id,
            'invoiceNumber' => 'P-00001',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/purchase/{$purchase->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_show_nonexistent_purchase_returns_404()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/purchase/99999');

        $response->assertStatus(404);
    }

    // ── Store (Create via Service) ──

    public function test_can_create_purchase_via_service()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $purchaseData = [
            'party_id' => $this->supplier->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 20,
                    'price' => 25.00,
                    'purchase_price' => 25.00,
                    'purchase_without_tax' => 25.00,
                    'purchase_with_tax' => 28.75,
                    'profit_percent' => 20,
                    'sales_price' => 35.00,
                    'wholesale_price' => 30.00,
                ],
            ],
            'totalAmount' => 500.00,
            'paidAmount' => 500.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'purchaseDate' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/purchase', $purchaseData);

        $response->assertStatus(200)
            ->assertJson(['message' => __('Data saved successfully.')]);

        $this->assertDatabaseHas('purchases', [
            'business_id' => $this->business->id,
            'party_id' => $this->supplier->id,
            'totalAmount' => 575.00,
        ]);
    }

    public function test_purchase_with_multiple_products()
    {
        $product1 = Product::factory()->create(['business_id' => $this->business->id]);
        $product2 = Product::factory()->create(['business_id' => $this->business->id]);

        $purchaseData = [
            'party_id' => $this->supplier->id,
            'products' => [
                [
                    'product_id' => $product1->id,
                    'quantities' => 10,
                    'price' => 20.00,
                    'purchase_price' => 20.00,
                    'purchase_without_tax' => 20.00,
                    'purchase_with_tax' => 23.00,
                    'profit_percent' => 25,
                    'sales_price' => 30.00,
                    'wholesale_price' => 25.00,
                ],
                [
                    'product_id' => $product2->id,
                    'quantities' => 5,
                    'price' => 40.00,
                    'purchase_price' => 40.00,
                    'purchase_without_tax' => 40.00,
                    'purchase_with_tax' => 46.00,
                    'profit_percent' => 15,
                    'sales_price' => 55.00,
                    'wholesale_price' => 48.00,
                ],
            ],
            'totalAmount' => 400.00,
            'paidAmount' => 400.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'purchaseDate' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/purchase', $purchaseData);

        $response->assertStatus(200);
    }

    // ── Update ──

    public function test_can_update_purchase()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $purchase = Purchase::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->supplier->id,
            'totalAmount' => 100.00,
            'paidAmount' => 100.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'invoiceNumber' => 'P-00001',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/purchase/{$purchase->id}", [
                'party_id' => $this->supplier->id,
                'totalAmount' => 250.00,
                'paidAmount' => 250.00,
                'dueAmount' => 0,
                'isPaid' => true,
                'paymentType' => 'cash',
                'purchaseDate' => now()->format('Y-m-d'),
                'products' => [
                    [
                        'product_id' => $product->id,
                        'purchase_without_tax' => 25.00,
                        'purchase_with_tax' => 28.75,
                        'profit_percent' => 20,
                        'sales_price' => 35.00,
                        'wholesale_price' => 30.00,
                        'quantities' => 10,
                    ],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'totalAmount' => 287.50,
        ]);
    }

    // ── Delete ──

    public function test_can_delete_purchase()
    {
        $purchase = Purchase::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->supplier->id,
            'invoiceNumber' => 'P-00001',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/purchase/{$purchase->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'canceled',
        ]);
    }

    // ── Purchase Status ──

    public function test_purchase_default_status_is_pending()
    {
        $purchase = Purchase::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->supplier->id,
            'invoiceNumber' => 'P-00001',
        ]);

        $this->assertEquals('pending', $purchase->status);
    }

    public function test_can_mark_purchase_as_received()
    {
        $purchase = Purchase::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->supplier->id,
            'invoiceNumber' => 'P-00001',
            'status' => 'pending',
        ]);

        $purchase->markAsReceived();

        $this->assertEquals('received', $purchase->fresh()->status);
        $this->assertNotNull($purchase->fresh()->received_at);
    }

    public function test_can_mark_purchase_as_partial()
    {
        $purchase = Purchase::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->supplier->id,
            'invoiceNumber' => 'P-00001',
            'status' => 'pending',
        ]);

        $purchase->markAsPartial();

        $this->assertEquals('partial', $purchase->fresh()->status);
    }

    public function test_can_cancel_purchase()
    {
        $purchase = Purchase::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->supplier->id,
            'invoiceNumber' => 'P-00001',
            'status' => 'pending',
        ]);

        $purchase->cancel();

        $this->assertEquals('canceled', $purchase->fresh()->status);
        $this->assertNotNull($purchase->fresh()->canceled_at);
    }

    // ── Payment Types ──

    public function test_purchase_with_credit_payment()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $purchaseData = [
            'party_id' => $this->supplier->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 10,
                    'price' => 50.00,
                    'purchase_price' => 50.00,
                    'purchase_without_tax' => 50.00,
                    'purchase_with_tax' => 57.50,
                    'profit_percent' => 20,
                    'sales_price' => 70.00,
                    'wholesale_price' => 60.00,
                ],
            ],
            'totalAmount' => 500.00,
            'paidAmount' => 0.00,
            'dueAmount' => 500.00,
            'isPaid' => false,
            'paymentType' => 'credit',
            'purchaseDate' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/purchase', $purchaseData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('purchases', [
            'business_id' => $this->business->id,
            'paymentType' => 'credit',
            'isPaid' => false,
        ]);
    }

    // ── Purchase Increases Stock ──

    public function test_purchase_increases_stock()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 50,
        ]);

        $purchaseData = [
            'party_id' => $this->supplier->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 30,
                    'price' => 25.00,
                    'purchase_price' => 25.00,
                    'purchase_without_tax' => 25.00,
                    'purchase_with_tax' => 28.75,
                    'profit_percent' => 20,
                    'sales_price' => 35.00,
                    'wholesale_price' => 30.00,
                ],
            ],
            'totalAmount' => 750.00,
            'paidAmount' => 750.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'purchaseDate' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/purchase', $purchaseData);

        $response->assertStatus(200);

        $stock->refresh();
        $this->assertEquals(80, $stock->productStock);
    }
}
