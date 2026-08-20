<?php

namespace Tests\Unit\Models;

use App\Models\WarehouseStock;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_warehouse_stock(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $stock = WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $this->assertDatabaseHas('warehouse_stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);
    }

    public function test_increase_stock(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $stock = WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $stock->increase(30);

        $this->assertEquals(80, $stock->fresh()->quantity);
    }

    public function test_decrease_stock(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $stock = WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $result = $stock->decrease(30);

        $this->assertTrue($result);
        $this->assertEquals(70, $stock->fresh()->quantity);
    }

    public function test_decrease_stock_insufficient(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $stock = WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        $result = $stock->decrease(50);

        $this->assertFalse($result);
        $this->assertEquals(20, $stock->fresh()->quantity);
    }

    public function test_scope_for_business(): void
    {
        $business1 = \App\Models\Business::factory()->create();
        $business2 = \App\Models\Business::factory()->create();
        $warehouse = Warehouse::factory()->create(['business_id' => $business1->id]);
        $product = Product::factory()->create();

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'business_id' => $business1->id,
        ]);
        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'business_id' => $business2->id,
        ]);

        $count = WarehouseStock::forBusiness($business1->id)->count();

        $this->assertEquals(1, $count);
    }

    public function test_scope_low_stock(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product1->id,
            'quantity' => 5,
        ]);
        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product2->id,
            'quantity' => 15,
        ]);

        $lowStockCount = WarehouseStock::lowStock()->count();

        $this->assertEquals(1, $lowStockCount);
    }

    public function test_scope_out_of_stock(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product1->id,
            'quantity' => 0,
        ]);
        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product2->id,
            'quantity' => -5,
        ]);
        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product2->id,
            'quantity' => 10,
        ]);

        $outOfStockCount = WarehouseStock::outOfStock()->count();

        $this->assertEquals(2, $outOfStockCount);
    }
}