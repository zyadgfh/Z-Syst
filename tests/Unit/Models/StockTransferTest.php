<?php

namespace Tests\Unit\Models;

use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('accounting')]
class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');
    }

    public function test_can_create_stock_transfer(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('stock_transfers', [
            'from_warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'status' => 'pending',
        ]);
    }

    public function test_can_be_completed(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $product = Product::factory()->create();

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse1->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'status' => 'pending',
        ]);

        $this->assertTrue($transfer->canBeCompleted());
    }

    public function test_cannot_be_completed_insufficient_stock(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $product = Product::factory()->create();

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse1->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'status' => 'pending',
        ]);

        $this->assertFalse($transfer->canBeCompleted());
    }

    public function test_cannot_be_completed_if_not_pending(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $product = Product::factory()->create();

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse1->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'status' => 'completed',
        ]);

        $this->assertFalse($transfer->canBeCompleted());
    }

    public function test_complete_transfer(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $product = Product::factory()->create();

        WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse1->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'status' => 'pending',
        ]);

        $result = $transfer->complete();

        $this->assertTrue($result);
        $this->assertEquals('completed', $transfer->fresh()->status);
        $this->assertEquals(30, WarehouseStock::where('warehouse_id', $warehouse1->id)->first()->quantity);
        $this->assertEquals(20, WarehouseStock::where('warehouse_id', $warehouse2->id)->first()->quantity);
    }

    public function test_cancel_transfer(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'status' => 'pending',
        ]);

        $result = $transfer->cancel();

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $transfer->fresh()->status);
    }

    public function test_cannot_cancel_if_not_pending(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'status' => 'completed',
        ]);

        $result = $transfer->cancel();

        $this->assertFalse($result);
    }

    public function test_scope_pending(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $product = Product::factory()->create();

        StockTransfer::factory()->create(['status' => 'pending']);
        StockTransfer::factory()->create(['status' => 'completed']);
        StockTransfer::factory()->create(['status' => 'cancelled']);

        $pendingCount = StockTransfer::pending()->count();

        $this->assertEquals(1, $pendingCount);
    }

    public function test_scope_completed(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $product = Product::factory()->create();

        StockTransfer::factory()->create(['status' => 'pending']);
        StockTransfer::factory()->create(['status' => 'completed']);
        StockTransfer::factory()->create(['status' => 'cancelled']);

        $completedCount = StockTransfer::completed()->count();

        $this->assertEquals(1, $completedCount);
    }

    public function test_get_status_label(): void
    {
        $pending = StockTransfer::factory()->create(['status' => 'pending']);
        $completed = StockTransfer::factory()->create(['status' => 'completed']);
        $cancelled = StockTransfer::factory()->create(['status' => 'cancelled']);

        $this->assertEquals('Pending', $pending->status_label);
        $this->assertEquals('Completed', $completed->status_label);
        $this->assertEquals('Cancelled', $cancelled->status_label);
    }

    public function test_scope_for_business(): void
    {
        $business1 = \App\Models\Business::factory()->create();
        $business2 = \App\Models\Business::factory()->create();
        $warehouse = Warehouse::factory()->create(['business_id' => $business1->id]);
        $product = Product::factory()->create();

        StockTransfer::factory()->create([
            'from_warehouse_id' => $warehouse->id,
            'business_id' => $business1->id,
            'product_id' => $product->id,
        ]);
        StockTransfer::factory()->create([
            'from_warehouse_id' => $warehouse->id,
            'business_id' => $business2->id,
            'product_id' => $product->id,
        ]);

        $count = StockTransfer::forBusiness($business1->id)->count();

        $this->assertEquals(1, $count);
    }

    public function test_scope_with_status(): void
    {
        StockTransfer::factory()->create(['status' => 'pending']);
        StockTransfer::factory()->create(['status' => 'completed']);

        $pendingCount = StockTransfer::withStatus('pending')->count();

        $this->assertEquals(1, $pendingCount);
    }
}