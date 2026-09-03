<?php

namespace Tests\Unit\Models;

use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create([
            'name' => 'Main Warehouse',
            'code' => 'WH001',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('warehouses', [
            'name' => 'Main Warehouse',
            'code' => 'WH001',
            'is_default' => true,
        ]);
    }

    public function test_scope_active(): void
    {
        Warehouse::factory()->create(['is_active' => true]);
        Warehouse::factory()->create(['is_active' => false]);

        $activeCount = Warehouse::active()->count();

        $this->assertEquals(1, $activeCount);
    }

    public function test_scope_default(): void
    {
        Warehouse::factory()->create(['is_default' => true]);
        Warehouse::factory()->create(['is_default' => false]);

        $defaultCount = Warehouse::default()->count();

        $this->assertEquals(1, $defaultCount);
    }

    public function test_scope_for_business(): void
    {
        $business1 = \App\Models\Business::factory()->create();
        $business2 = \App\Models\Business::factory()->create();

        Warehouse::factory()->create(['business_id' => $business1->id]);
        Warehouse::factory()->create(['business_id' => $business1->id]);
        Warehouse::factory()->create(['business_id' => $business2->id]);

        $count = Warehouse::forBusiness($business1->id)->count();

        $this->assertEquals(2, $count);
    }

    public function test_get_stock_for_product(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        \App\Models\WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $stock = $warehouse->getStockForProduct($product->id);

        $this->assertNotNull($stock);
        $this->assertEquals(50, $stock->quantity);
    }

    public function test_has_sufficient_stock(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        \App\Models\WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $this->assertTrue($warehouse->hasSufficientStock($product->id, 30));
        $this->assertFalse($warehouse->hasSufficientStock($product->id, 60));
    }

    public function test_get_total_products_attribute(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        \App\Models\WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product1->id,
            'quantity' => 10,
        ]);
        \App\Models\WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product2->id,
            'quantity' => 20,
        ]);

        $this->assertEquals(30, $warehouse->total_products);
    }
}