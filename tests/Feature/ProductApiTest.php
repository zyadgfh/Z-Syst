<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
    }

    // ── Authentication ──

    public function test_unauthenticated_user_cannot_access_products()
    {
        $response = $this->getJson('/api/v1/products');
        $response->assertStatus(401);
    }

    // ── Index (List) ──

    public function test_can_list_products()
    {
        Product::factory(3)->create(['business_id' => $this->business->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);
    }

    public function test_list_products_is_paginated()
    {
        Product::factory(15)->create(['business_id' => $this->business->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/products?per_page=5');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(5, $data['data']);
    }

    public function test_can_search_products_by_name()
    {
        Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'Paracetamol 500mg',
        ]);
        Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'Ibuprofen 200mg',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/products?search=Paracetamol');

        $response->assertStatus(200);
    }

    // ── Store (Create) ──

    public function test_can_create_product()
    {
        $category = Category::factory()->create(['business_id' => $this->business->id]);
        $unit = Unit::factory()->create(['business_id' => $this->business->id]);
        $manufacturer = Manufacturer::factory()->create(['business_id' => $this->business->id]);

        $productData = [
            'productName' => 'Amoxicillin 250mg',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'manufacturer_id' => $manufacturer->id,
            'purchase_without_tax' => 15.00,
            'purchase_with_tax' => 17.25,
            'profit_percent' => 30,
            'sales_price' => 22.50,
            'wholesale_price' => 20.00,
            'alert_qty' => 10,
            'productCode' => 'AMX-001',
            'tax_type' => 'exclusive',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(200)
            ->assertJson(['message' => __('Data saved successfully.')]);

        $this->assertDatabaseHas('products', [
            'productName' => 'Amoxicillin 250mg',
            'business_id' => $this->business->id,
            'productCode' => 'AMX-001',
        ]);
    }

    public function test_cannot_create_product_without_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['productName', 'category_id']);
    }

    // ── Show ──

    public function test_can_show_product()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);
    }

    public function test_show_nonexistent_product_returns_404()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/products/99999');

        $response->assertStatus(404);
    }

    // ── Update ──

    public function test_can_update_product()
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'Original Name',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/products/{$product->id}", [
                'productName' => 'Updated Name',
                'category_id' => $product->category_id,
                'unit_id' => $product->unit_id,
                'manufacturer_id' => $product->manufacturer_id,
                'purchase_without_tax' => $product->purchase_without_tax,
                'purchase_with_tax' => $product->purchase_with_tax,
                'sales_price' => $product->sales_price,
                'wholesale_price' => $product->wholesale_price,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'productName' => 'Updated Name',
        ]);
    }

    // ── Delete ──

    public function test_can_delete_product()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    // ── Stock Update ──

    public function test_can_update_product_stock()
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'purchase_without_tax' => 10.00,
            'purchase_with_tax' => 11.50,
            'sales_price' => 15.00,
            'wholesale_price' => 13.00,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/stock-update/{$product->id}", [
                'purchase_without_tax' => 10.00,
                'purchase_with_tax' => 11.50,
                'sales_price' => 15.00,
                'wholesale_price' => 13.00,
                'qty' => 50,
                'batch_no' => 'BATCH-001',
                'expire_date' => now()->addYear()->format('Y-m-d'),
            ]);

        $response->assertStatus(200);
    }

    public function test_stock_update_requires_qty()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/stock-update/{$product->id}", [
                'purchase_without_tax' => 10.00,
                'purchase_with_tax' => 11.50,
                'sales_price' => 15.00,
                'wholesale_price' => 13.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['qty']);
    }

    // ── Stocks with Product ──

    public function test_can_list_stocks_with_product()
    {
        Product::factory(3)->create(['business_id' => $this->business->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/stocks-with-product');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);
    }
}
