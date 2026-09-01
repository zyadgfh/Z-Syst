<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $user;
    private Party $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->customer = Party::factory()->create([
            'business_id' => $this->business->id,
            'type' => 'customer',
        ]);
    }

    // ── Authentication ──

    public function test_unauthenticated_user_cannot_access_sales()
    {
        $response = $this->getJson('/api/v1/sales');
        $response->assertStatus(401);
    }

    // ── Index (List) ──

    public function test_can_list_sales()
    {
        Sale::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->customer->id,
            'invoiceNumber' => 'S-00001',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sales');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_list_sales_is_paginated()
    {
        for ($i = 1; $i <= 12; $i++) {
            Sale::factory()->create([
                'business_id' => $this->business->id,
                'user_id' => $this->user->id,
                'party_id' => $this->customer->id,
                'invoiceNumber' => 'S-' . str_pad($i, 5, '0', STR_PAD_LEFT),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sales?per_page=5');

        $response->assertStatus(200);
    }

    // ── Show ──

    public function test_can_show_sale()
    {
        $sale = Sale::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->customer->id,
            'invoiceNumber' => 'S-00001',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sales/{$sale->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_show_nonexistent_sale_returns_404()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sales/99999');

        $response->assertStatus(404);
    }

    // ── Store (Create via Service) ──

    public function test_can_create_sale_via_service()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
        ]);

        $saleData = [
            'party_id' => $this->customer->id,
            'saleDate' => now()->format('Y-m-d'),
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 10,
                    'price' => 50.00,
                    'purchase_price' => 30.00,
                    'lossProfit' => 0,
                ],
            ],
            'totalAmount' => 500.00,
            'paidAmount' => 500.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales', $saleData);

        $response->assertStatus(200)
            ->assertJson(['message' => __('Data saved successfully.')]);

        $this->assertDatabaseHas('sales', [
            'business_id' => $this->business->id,
            'party_id' => $this->customer->id,
        ]);
    }

    public function test_sale_with_multiple_products()
    {
        $product1 = Product::factory()->create(['business_id' => $this->business->id]);
        $product2 = Product::factory()->create(['business_id' => $this->business->id]);

        Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product1->id,
            'productStock' => 50,
        ]);
        Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product2->id,
            'productStock' => 30,
        ]);

        $saleData = [
            'party_id' => $this->customer->id,
            'saleDate' => now()->format('Y-m-d'),
            'products' => [
                [
                    'product_id' => $product1->id,
                    'quantities' => 5,
                    'price' => 50.00,
                    'purchase_price' => 30.00,
                    'lossProfit' => 0,
                ],
                [
                    'product_id' => $product2->id,
                    'quantities' => 3,
                    'price' => 75.00,
                    'purchase_price' => 45.00,
                    'lossProfit' => 0,
                ],
            ],
            'totalAmount' => 475.00,
            'paidAmount' => 475.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales', $saleData);

        $response->assertStatus(200);
    }

    // ── Update ──

    public function test_can_update_sale()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        Stock::factory()->create(['business_id' => $this->business->id, 'product_id' => $product->id, 'productStock' => 100]);
        $sale = Sale::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->customer->id,
            'totalAmount' => 100.00,
            'paidAmount' => 100.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
            'invoiceNumber' => 'S-00001',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/{$sale->id}", [
                'party_id' => $this->customer->id,
                'totalAmount' => 200.00,
                'paidAmount' => 200.00,
                'dueAmount' => 0,
                'isPaid' => true,
                'paymentType' => 'Cash',
                'saleDate' => now()->format('Y-m-d'),
                'products' => [
                    [
                        'product_id' => $product->id,
                        'price' => 50.00,
                        'quantities' => 4,
                        'purchase_price' => 30.00,
                        'lossProfit' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
        ]);
    }

    // ── Delete ──

    public function test_can_delete_sale()
    {
        $sale = Sale::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'party_id' => $this->customer->id,
            'invoiceNumber' => 'S-00001',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/sales/{$sale->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('sales', ['id' => $sale->id]);
    }

    // ── Stock Deduction ──

    public function test_sale_deducts_stock()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
        ]);

        $saleData = [
            'party_id' => $this->customer->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 25,
                    'price' => 50.00,
                    'purchase_price' => 30.00,
                    'lossProfit' => 0,
                ],
            ],
            'saleDate' => now()->format('Y-m-d'),
            'totalAmount' => 1250.00,
            'paidAmount' => 1250.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales', $saleData);

        $response->assertStatus(200);

        $stock->refresh();
        $this->assertEquals(75, $stock->productStock);
    }

    // ── Payment Types ──

    public function test_sale_with_card_payment()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 50,
        ]);

        $saleData = [
            'party_id' => $this->customer->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 2,
                    'price' => 100.00,
                    'purchase_price' => 60.00,
                    'lossProfit' => 0,
                ],
            ],
            'saleDate' => now()->format('Y-m-d'),
            'totalAmount' => 200.00,
            'paidAmount' => 200.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Card',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales', $saleData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sales', [
            'business_id' => $this->business->id,
            'paymentType' => 'Card',
        ]);
    }

    public function test_sale_with_credit_payment()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 50,
        ]);

        $saleData = [
            'party_id' => $this->customer->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 1,
                    'price' => 100.00,
                    'purchase_price' => 60.00,
                    'lossProfit' => 0,
                ],
            ],
            'saleDate' => now()->format('Y-m-d'),
            'totalAmount' => 100.00,
            'paidAmount' => 0.00,
            'dueAmount' => 100.00,
            'isPaid' => false,
            'paymentType' => 'Credit',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales', $saleData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sales', [
            'business_id' => $this->business->id,
            'paymentType' => 'Credit',
            'isPaid' => false,
        ]);
    }
}
