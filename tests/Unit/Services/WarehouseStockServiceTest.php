<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\WarehouseStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class WarehouseStockServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Product $product;

    private WarehouseStockService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);
        $this->service = new WarehouseStockService;

        Auth::login($this->user);
    }

    public function test_can_create_warehouse(): void
    {
        $data = [
            'business_id' => $this->business->id,
            'name' => 'Main Pharmacy',
            'code' => 'WH-MAIN',
            'location' => 'Downtown',
            'is_default' => true,
            'is_active' => true,
        ];

        $warehouse = $this->service->createWarehouse($data);

        $this->assertEquals('Main Pharmacy', $warehouse->name);
        $this->assertEquals('WH-MAIN', $warehouse->code);
        $this->assertTrue($warehouse->is_default);
    }

    public function test_can_update_warehouse(): void
    {
        $warehouse = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Old Name',
            'code' => 'WH-OLD',
            'is_default' => false,
            'is_active' => true,
        ]);

        $updated = $this->service->updateWarehouse($warehouse, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
    }

    public function test_get_default_warehouse_returns_active_default(): void
    {
        Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Default WH',
            'code' => 'WH-DEF',
            'is_default' => true,
            'is_active' => true,
        ]);

        Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Inactive Default',
            'code' => 'WH-INACT',
            'is_default' => true,
            'is_active' => false,
        ]);

        $default = $this->service->getDefaultWarehouse($this->business->id);

        $this->assertNotNull($default);
        $this->assertEquals('Default WH', $default->name);
    }

    public function test_get_default_warehouse_returns_null_when_none_exists(): void
    {
        $default = $this->service->getDefaultWarehouse($this->business->id);

        $this->assertNull($default);
    }

    public function test_get_warehouses_returns_active_only(): void
    {
        Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Active WH',
            'code' => 'WH-ACT',
            'is_default' => false,
            'is_active' => true,
        ]);

        Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Inactive WH',
            'code' => 'WH-INACT',
            'is_default' => false,
            'is_active' => false,
        ]);

        $warehouses = $this->service->getWarehouses($this->business->id);

        $this->assertCount(1, $warehouses);
        $this->assertEquals('Active WH', $warehouses[0]['name']);
    }

    public function test_get_stock_by_product_and_warehouse(): void
    {
        $wh1 = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'WH1',
            'code' => 'WH001',
        ]);

        $wh2 = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'WH2',
            'code' => 'WH002',
        ]);

        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $wh1->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ]);

        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $wh2->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
        ]);

        $this->assertEquals(100, $this->service->getStockByWarehouse($this->product->id, $wh1->id, $this->business->id));
        $this->assertEquals(50, $this->service->getStockByWarehouse($this->product->id, $wh2->id, $this->business->id));
        $this->assertEquals(150, $this->service->getStockByWarehouse($this->product->id, null, $this->business->id));
    }

    public function test_transfer_stock_updates_quantities(): void
    {
        $fromWh = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'From WH',
            'code' => 'WH-FROM',
        ]);

        $toWh = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'To WH',
            'code' => 'WH-TO',
        ]);

        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $fromWh->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ]);

        $transfer = $this->service->transferStock(
            $this->business->id,
            $this->product->id,
            $fromWh->id,
            $toWh->id,
            30
        );

        $this->assertEquals('completed', $transfer->status);

        $fromStock = WarehouseStock::where('warehouse_id', $fromWh->id)
            ->where('product_id', $this->product->id)
            ->first();

        $toStock = WarehouseStock::where('warehouse_id', $toWh->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(70, $fromStock->quantity);
        $this->assertEquals(30, $toStock->quantity);
    }

    public function test_transfer_stock_throws_on_same_warehouse(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot transfer to the same warehouse.');

        $wh = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'WH',
            'code' => 'WH-SAME',
        ]);

        $this->service->transferStock(
            $this->business->id,
            $this->product->id,
            $wh->id,
            $wh->id,
            10
        );
    }

    public function test_transfer_stock_throws_on_insufficient_quantity(): void
    {
        $fromWh = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'From WH',
            'code' => 'WH-FROM',
        ]);

        $toWh = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'To WH',
            'code' => 'WH-TO',
        ]);

        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $fromWh->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock in source warehouse.');

        $this->service->transferStock(
            $this->business->id,
            $this->product->id,
            $fromWh->id,
            $toWh->id,
            50
        );
    }
}
