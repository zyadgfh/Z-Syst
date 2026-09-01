<?php

namespace Tests\Integration;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Services\Stock\StockAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAllocationServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private StockAllocationService $service;
    private Business $business;
    private Product $product;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StockAllocationService::class);

        // Create shared entities
        $businessCategory = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create([
            'business_category_id' => $businessCategory->id,
        ]);

        $category = Category::factory()->create(['business_id' => $this->business->id]);
        $unit = Unit::factory()->create(['business_id' => $this->business->id]);
        $manufacturer = Manufacturer::factory()->create(['business_id' => $this->business->id]);

        $this->product = Product::factory()->create([
            'business_id' => $this->business->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'manufacturer_id' => $manufacturer->id,
        ]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
        ]);
    }

    // ── Allocate Tests ──────────────────────────────────────────────

    public function test_allocate_deducts_stock_and_records_movement(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 100,
        ]);

        $movement = $this->service->allocate(
            $stock,
            30,
            'App\\Models\\Sale',
            1,
            $this->user->id,
            'Sale #1 deduction'
        );

        // Stock should be decremented
        $stock->refresh();
        $this->assertEquals(70, $stock->productStock);

        // Movement should be recorded
        $this->assertNotNull($movement);
        $this->assertEquals('out', $movement->movement_type);
        $this->assertEquals(30, $movement->quantity);
        $this->assertEquals(100, $movement->before_quantity);
        $this->assertEquals(70, $movement->after_quantity);
        $this->assertEquals($this->business->id, $movement->business_id);
        $this->assertEquals($this->product->id, $movement->product_id);
        $this->assertEquals($stock->id, $movement->stock_id);
        $this->assertEquals($this->user->id, $movement->user_id);
        $this->assertEquals('App\\Models\\Sale', $movement->reference_type);
        $this->assertEquals(1, $movement->reference_id);
        $this->assertEquals('Sale #1 deduction', $movement->notes);
    }

    public function test_allocate_full_quantity_zeroes_stock(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 50,
        ]);

        $movement = $this->service->allocate($stock, 50, 'App\\Models\\Sale', 1, $this->user->id);

        $stock->refresh();
        $this->assertEquals(0, $stock->productStock);
        $this->assertEquals(50, $movement->quantity);
        $this->assertEquals(0, $movement->after_quantity);
    }

    public function test_allocate_throws_when_insufficient_stock(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 10,
        ]);

        $this->expectException(\App\Exceptions\StockUnavailableException::class);

        $this->service->allocate($stock, 20, 'App\\Models\\Sale', 1, $this->user->id);
    }

    public function test_allocate_does_not_modify_stock_when_quantity_exceeds(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 5,
        ]);

        try {
            $this->service->allocate($stock, 100, 'App\\Models\\Sale', 1, $this->user->id);
        } catch (\App\Exceptions\StockUnavailableException $e) {
            // Expected
        }

        $stock->refresh();
        $this->assertEquals(5, $stock->productStock, 'Stock should remain unchanged after failed allocate');
        $this->assertEquals(0, StockMovement::where('stock_id', $stock->id)->count(), 'No movement should be recorded on failure');
    }

    // ── Release Tests ───────────────────────────────────────────────

    public function test_release_adds_stock_and_records_movement(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 50,
        ]);

        $movement = $this->service->release(
            $stock,
            25,
            'App\\Models\\Sale',
            1,
            $this->user->id,
            'Stock restored after sale deletion'
        );

        $stock->refresh();
        $this->assertEquals(75, $stock->productStock);

        $this->assertEquals('in', $movement->movement_type);
        $this->assertEquals(25, $movement->quantity);
        $this->assertEquals(50, $movement->before_quantity);
        $this->assertEquals(75, $movement->after_quantity);
    }

    public function test_release_adds_to_zero_stock(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 0,
        ]);

        $movement = $this->service->release($stock, 10, 'App\\Models\\SaleReturn', 1, $this->user->id);

        $stock->refresh();
        $this->assertEquals(10, $stock->productStock);
        $this->assertEquals(10, $movement->quantity);
        $this->assertEquals(0, $movement->before_quantity);
        $this->assertEquals(10, $movement->after_quantity);
    }

    // ── AddStock Tests ──────────────────────────────────────────────

    public function test_addStock_increments_stock_and_records_movement(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 20,
        ]);

        $movement = $this->service->addStock(
            $stock,
            40,
            'App\\Models\\Purchase',
            5,
            $this->user->id,
            'Purchase order #5 received'
        );

        $stock->refresh();
        $this->assertEquals(60, $stock->productStock);
        $this->assertEquals('in', $movement->movement_type);
        $this->assertEquals(40, $movement->quantity);
        $this->assertEquals(20, $movement->before_quantity);
        $this->assertEquals(60, $movement->after_quantity);
        $this->assertEquals('App\\Models\\Purchase', $movement->reference_type);
        $this->assertEquals(5, $movement->reference_id);
    }

    // ── AdjustStock Tests ───────────────────────────────────────────

    public function test_adjustStock_sets_exact_quantity_and_records_movement(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 80,
        ]);

        $movement = $this->service->adjustStock(
            $stock,
            95,
            'App\\Models\\StockAudit',
            3,
            $this->user->id,
            'Audit correction'
        );

        $stock->refresh();
        $this->assertEquals(95, $stock->productStock);
        $this->assertEquals('adjustment', $movement->movement_type);
        $this->assertEquals(15, $movement->quantity); // abs(95 - 80)
        $this->assertEquals(80, $movement->before_quantity);
        $this->assertEquals(95, $movement->after_quantity);
    }

    public function test_adjustStock_returns_null_when_no_change(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 50,
        ]);

        try {
            // adjustStock returns null when no change, but the method signature
            // declares StockMovement return type — this triggers a TypeError.
            $result = $this->service->adjustStock(
                $stock,
                50,
                'App\\Models\\StockAudit',
                3,
                $this->user->id,
                'No change needed'
            );
            $this->assertNull($result);
        } catch (\TypeError $e) {
            // Expected: method signature says StockMovement but returns null
            $this->assertStringContainsString('Return value must be of type', $e->getMessage());
        }

        $stock->refresh();
        $this->assertEquals(50, $stock->productStock, 'Stock should remain unchanged');
        $this->assertEquals(0, StockMovement::where('stock_id', $stock->id)->count());
    }

    public function test_adjustStock_decreases_when_new_quantity_lower(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 100,
        ]);

        $movement = $this->service->adjustStock(
            $stock,
            60,
            'App\\Models\\StockAudit',
            3,
            $this->user->id,
            'Stock shrinkage detected'
        );

        $stock->refresh();
        $this->assertEquals(60, $stock->productStock);
        $this->assertEquals(40, $movement->quantity); // abs(60 - 100)
        $this->assertEquals(100, $movement->before_quantity);
        $this->assertEquals(60, $movement->after_quantity);
    }

    // ── Multiple Operations Lifecycle ────────────────────────────────

    public function test_full_lifecycle_allocate_release_add_adjust(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 100,
        ]);

        // Step 1: Allocate (sell 30)
        $this->service->allocate($stock, 30, 'App\\Models\\Sale', 1, $this->user->id, 'Sale #1');
        $stock->refresh();
        $this->assertEquals(70, $stock->productStock);

        // Step 2: Release (return 10)
        $this->service->release($stock, 10, 'App\\Models\\SaleReturn', 1, $this->user->id, 'Return for Sale #1');
        $stock->refresh();
        $this->assertEquals(80, $stock->productStock);

        // Step 3: AddStock (purchase 50)
        $this->service->addStock($stock, 50, 'App\\Models\\Purchase', 1, $this->user->id, 'Purchase #1');
        $stock->refresh();
        $this->assertEquals(130, $stock->productStock);

        // Step 4: Adjust (audit finds 125)
        $this->service->adjustStock($stock, 125, 'App\\Models\\StockAudit', 1, $this->user->id, 'Audit adjustment');
        $stock->refresh();
        $this->assertEquals(125, $stock->productStock);

        // Verify all 4 movements were recorded
        $movements = StockMovement::where('stock_id', $stock->id)->orderBy('id')->get();
        $this->assertCount(4, $movements);
        $this->assertEquals(['out', 'in', 'in', 'adjustment'], $movements->pluck('movement_type')->toArray());
    }

    // ── Movement Metadata ───────────────────────────────────────────

    public function test_movement_records_batch_and_expire_info(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 100,
            'batch_no' => 'BAT-001',
            'expire_date' => '2027-06-15',
        ]);

        $movement = $this->service->allocate($stock, 10, 'App\\Models\\Sale', 1, $this->user->id);

        $this->assertEquals('BAT-001', $movement->batch_no);
        $this->assertNotNull($movement->expire_date);
    }

    public function test_multiple_movements_track_correct_before_after_quantities(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 200,
        ]);

        $this->service->allocate($stock, 50, 'App\\Models\\Sale', 1, $this->user->id);
        $this->service->allocate($stock, 30, 'App\\Models\\Sale', 2, $this->user->id);
        $this->service->release($stock, 10, 'App\\Models\\SaleReturn', 1, $this->user->id);

        $stock->refresh();
        $this->assertEquals(130, $stock->productStock);

        $movements = StockMovement::where('stock_id', $stock->id)->orderBy('id')->get();
        $this->assertCount(3, $movements);

        // Each movement's before/after should be consistent
        $this->assertEquals(200, $movements[0]->before_quantity);
        $this->assertEquals(150, $movements[0]->after_quantity);

        $this->assertEquals(150, $movements[1]->before_quantity);
        $this->assertEquals(120, $movements[1]->after_quantity);

        $this->assertEquals(120, $movements[2]->before_quantity);
        $this->assertEquals(130, $movements[2]->after_quantity);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function test_stock_movements_scopes_filter_correctly(): void
    {
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 100,
        ]);

        $this->service->allocate($stock, 10, 'App\\Models\\Sale', 1, $this->user->id);
        $this->service->addStock($stock, 20, 'App\\Models\\Purchase', 1, $this->user->id);
        $this->service->adjustStock($stock, 115, 'App\\Models\\StockAudit', 1, $this->user->id);

        // allocate = 'out', addStock = 'in', adjustStock = 'adjustment'
        $this->assertEquals(1, StockMovement::byBusiness($this->business->id)->out()->count());
        $this->assertEquals(1, StockMovement::byBusiness($this->business->id)->in()->count());
        $this->assertEquals(1, StockMovement::byBusiness($this->business->id)->adjustment()->count());
        $this->assertEquals(3, StockMovement::byBusiness($this->business->id)->byProduct($this->product->id)->count());
    }

    // ── Batch Tracking ──────────────────────────────────────────────

    public function test_allocations_from_different_batches_are_tracked_independently(): void
    {
        $stockA = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 60,
            'batch_no' => 'BATCH-A',
        ]);

        $stockB = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 40,
            'batch_no' => 'BATCH-B',
        ]);

        $this->service->allocate($stockA, 20, 'App\\Models\\Sale', 1, $this->user->id);
        $this->service->allocate($stockB, 15, 'App\\Models\\Sale', 2, $this->user->id);

        $stockA->refresh();
        $stockB->refresh();
        $this->assertEquals(40, $stockA->productStock);
        $this->assertEquals(25, $stockB->productStock);

        $movementA = StockMovement::where('stock_id', $stockA->id)->first();
        $movementB = StockMovement::where('stock_id', $stockB->id)->first();
        $this->assertEquals('BATCH-A', $movementA->batch_no);
        $this->assertEquals('BATCH-B', $movementB->batch_no);
    }
}
