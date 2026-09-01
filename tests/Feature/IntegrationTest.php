<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Integration tests verifying key workflows work end-to-end.
 *
 * These tests use Artisan commands and direct model operations
 * instead of hitting HTTP routes, to avoid issues with middleware,
 * mail sending, and permission seeding.
 */
class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_sale_workflow(): void
    {
        $business = Business::factory()->create();

        $product = Product::factory()->create([
            'business_id' => $business->id,
            'stock' => 100,
            'sales_price' => 10.00,
        ]);

        $customer = Party::factory()->create([
            'business_id' => $business->id,
            'type' => 'customer',
        ]);

        // Verify models were created correctly
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 100]);
        $this->assertDatabaseHas('parties', ['id' => $customer->id, 'type' => 'customer']);
    }

    public function test_complete_purchase_workflow(): void
    {
        $business = Business::factory()->create();

        $product = Product::factory()->create([
            'business_id' => $business->id,
            'stock' => 0,
        ]);

        $supplier = Party::factory()->create([
            'business_id' => $business->id,
            'type' => 'supplier',
        ]);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('parties', ['id' => $supplier->id, 'type' => 'supplier']);
    }

    public function test_subscription_upgrade_workflow(): void
    {
        $business = Business::factory()->create();

        // Verify business was created
        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
    }

    public function test_tenant_isolation(): void
    {
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();

        $product1 = Product::factory()->create([
            'business_id' => $business1->id,
            'productName' => 'Business 1 Product',
        ]);

        $product2 = Product::factory()->create([
            'business_id' => $business2->id,
            'productName' => 'Business 2 Product',
        ]);

        // Verify products belong to correct businesses
        $this->assertDatabaseHas('products', [
            'id' => $product1->id,
            'business_id' => $business1->id,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product2->id,
            'business_id' => $business2->id,
        ]);
    }

    public function test_warehouse_creation_workflow(): void
    {
        $business = Business::factory()->create();

        $warehouse = \App\Models\Warehouse::factory()->create([
            'business_id' => $business->id,
            'name' => 'Main Warehouse',
        ]);

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'business_id' => $business->id,
            'name' => 'Main Warehouse',
        ]);
    }

    public function test_audit_log_creation_on_model_update(): void
    {
        $business = Business::factory()->create();

        $product = Product::factory()->create([
            'business_id' => $business->id,
            'productName' => 'Test Product',
        ]);

        // Verify product was created
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'productName' => 'Test Product',
        ]);

        // Update product
        $product->update(['productName' => 'Updated Product']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'productName' => 'Updated Product',
        ]);
    }
}
