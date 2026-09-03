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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration test covering the full StockMovement lifecycle:
 * product → stock batches → allocate/release/addStock/adjust → movement records.
 */
class StockMovementFlowIntegrationTest extends TestCase
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

    /**
     * Full product lifecycle: purchase → sell → return → audit adjustment.
     */
    public function test_full_product_stock_lifecycle(): void
    {
        // ── Phase 1: Purchase (stock in) ──────────────────────────────
        $stock = Stock::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 0,
            'batch_no' => 'PO-2026-001',
            'expire_date' => '2027-12-31',
        ]);

        $this->service->addStock($stock, 100, 'App\\Models\\Purchase', 1, $this->user->id, 'Initial purchase');

        $stock->refresh();
        $this->assertEquals(100, $stock->productStock);

        // ── Phase 2: First sale (allocate 40) ────────────────────────
        $this->service->allocate($stock, 40, 'App\\Models\\Sale', 1, $this->user->id, 'Sale #1');

        $stock->refresh();
        $this->assertEquals(60, $stock->productStock);

        // ── Phase 3: Second sale (allocate 20) ───────────────────────
        $this->service->allocate($stock, 20, 'App\\Models\\Sale', 2, $this->user->id, 'Sale #2');

        $stock->refresh();
        $this->assertEquals(40, $stock->productStock);

        // ── Phase 4: Sale return (release 5) ─────────────────────────
        $this->service->release($stock, 5, 'App\\Models\\SaleReturn', 1, $this->user->id, 'Return from Sale #1');

        $stock->refresh();
        $this->assertEquals(45, $stock->productStock);

        // ── Phase 5: Audit adjustment (correct to 42) ────────────────
        $this->service->adjustStock($stock, 42, 'App\\Models\\StockAudit', 1, $this->user->id, 'Physical count correction');

        $stock->refresh();
        $this->assertEquals(42, $stock->productStock);

        // ── Verify full movement trail ────────────────────────────────
        $movements = StockMovement::where('stock_id', $stock->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(5, $movements);

        // Verify each movement
        $expectedMovements = [
            ['movement_type' => 'in', 'quantity' => 100, 'before_quantity' => 0, 'after_quantity' => 100],
            ['movement_type' => 'out', 'quantity' => 40, 'before_quantity' => 100, 'after_quantity' => 60],
            ['movement_type' => 'out', 'quantity' => 20, 'before_quantity' => 60, 'after_quantity' => 40],
            ['movement_type' => 'in', 'quantity' => 5, 'before_quantity' => 40, 'after_quantity' => 45],
            ['movement_type' => 'adjustment', 'quantity' => 3, 'before_quantity' => 45, 'after_quantity' => 42],
        ];

        foreach ($expectedMovements as $index => $expected) {
            $movement = $movements[$index];
            $this->assertEquals($expected['movement_type'], $movement->movement_type, "Movement #{$index} type mismatch");
            $this->assertEquals($expected['quantity'], $movement->quantity, "Movement #{$index} quantity mismatch");
            $this->assertEquals($expected['before_quantity'], $movement->before_quantity, "Movement #{$index} before_quantity mismatch");
            $this->assertEquals($expected['after_quantity'], $movement->after_quantity, "Movement #{$index} after_quantity mismatch");
        }
    }

    /**
     * Test concurrent batches for the same product.
     */
    public function test_multi_batch_stock_management(): void
    {
        $batch1 = Stock::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 50,
            'batch_no' => 'BATCH-001',
            'expire_date' => '2026-12-31',
        ]);

        $batch2 = Stock::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 80,
            'batch_no' => 'BATCH-002',
            'expire_date' => '2027-06-30',
        ]);

        // Sell from both batches
        $this->service->allocate($batch1, 30, 'App\\Models\\Sale', 1, $this->user->id, 'Sale #1 from batch 1');
        $this->service->allocate($batch2, 50, 'App\\Models\\Sale', 2, $this->user->id, 'Sale #2 from batch 2');

        $batch1->refresh();
        $batch2->refresh();
        $this->assertEquals(20, $batch1->productStock);
        $this->assertEquals(30, $batch2->productStock);

        // Return to batch 2
        $this->service->release($batch2, 10, 'App\\Models\\SaleReturn', 1, $this->user->id, 'Partial return from Sale #2');
        $batch2->refresh();
        $this->assertEquals(40, $batch2->productStock);

        // Verify movements are per-batch
        $batch1Movements = StockMovement::where('stock_id', $batch1->id)->count();
        $batch2Movements = StockMovement::where('stock_id', $batch2->id)->count();
        $this->assertEquals(1, $batch1Movements);
        $this->assertEquals(2, $batch2Movements);
    }

    /**
     * Test that movements are atomic (transactional integrity).
     */
    public function test_stock_movement_atomicity(): void
    {
        $stock = Stock::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 50,
            'batch_no' => 'ATOMIC-001',
        ]);

        // Perform 10 allocations of 5 units each
        for ($i = 1; $i <= 10; $i++) {
            $this->service->allocate($stock, 5, 'App\\Models\\Sale', $i, $this->user->id, "Sale #{$i}");
        }

        $stock->refresh();
        $this->assertEquals(0, $stock->productStock);

        $movements = StockMovement::where('stock_id', $stock->id)->get();
        $this->assertCount(10, $movements);
        $this->assertEquals(50, $movements->sum('quantity'));

        // Verify quantity chain
        $prevAfter = 0;
        $firstMovement = true;
        foreach ($movements as $m) {
            if ($firstMovement) {
                $this->assertEquals(50, $m->before_quantity);
                $this->assertEquals(45, $m->after_quantity);
                $firstMovement = false;
            }
        }
    }

    /**
     * Test that allocate → release → allocate again maintains consistency.
     */
    public function test_allocate_release_reallocate_cycle(): void
    {
        $stock = Stock::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 30,
            'batch_no' => 'CYCLE-001',
        ]);

        // Allocate 20
        $this->service->allocate($stock, 20, 'App\\Models\\Sale', 1, $this->user->id);
        $stock->refresh();
        $this->assertEquals(10, $stock->productStock);

        // Release 15 (return)
        $this->service->release($stock, 15, 'App\\Models\\SaleReturn', 1, $this->user->id);
        $stock->refresh();
        $this->assertEquals(25, $stock->productStock);

        // Allocate 25 again
        $this->service->allocate($stock, 25, 'App\\Models\\Sale', 2, $this->user->id);
        $stock->refresh();
        $this->assertEquals(0, $stock->productStock);

        // Release all
        $this->service->release($stock, 10, 'App\\Models\\SaleReturn', 2, $this->user->id);
        $stock->refresh();
        $this->assertEquals(10, $stock->productStock);

        // Verify movements count
        $this->assertCount(4, StockMovement::where('stock_id', $stock->id)->get());
    }

    /**
     * Test movements across different products don't interfere.
     */
    public function test_movements_isolated_per_product(): void
    {
        $category = Category::factory()->create(['business_id' => $this->business->id]);
        $unit = Unit::factory()->create(['business_id' => $this->business->id]);
        $manufacturer = Manufacturer::factory()->create(['business_id' => $this->business->id]);

        $product2 = Product::factory()->create([
            'business_id' => $this->business->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'manufacturer_id' => $manufacturer->id,
        ]);

        $stock1 = Stock::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 50,
            'batch_no' => 'P1-001',
        ]);

        $stock2 = Stock::create([
            'business_id' => $this->business->id,
            'product_id' => $product2->id,
            'productStock' => 30,
            'batch_no' => 'P2-001',
        ]);

        $this->service->allocate($stock1, 10, 'App\\Models\\Sale', 1, $this->user->id);
        $this->service->allocate($stock2, 5, 'App\\Models\\Sale', 2, $this->user->id);
        $this->service->addStock($stock1, 20, 'App\\Models\\Purchase', 1, $this->user->id);

        $stock1->refresh();
        $stock2->refresh();
        $this->assertEquals(60, $stock1->productStock);
        $this->assertEquals(25, $stock2->productStock);

        // Verify movement isolation
        $product1Movements = StockMovement::byProduct($this->product->id)->count();
        $product2Movements = StockMovement::byProduct($product2->id)->count();
        $this->assertEquals(2, $product1Movements);
        $this->assertEquals(1, $product2Movements);

        // Total movements
        $this->assertCount(3, StockMovement::all());
    }

    /**
     * Test movement reference_type/reference_id tracking.
     */
    public function test_movement_references_are_tracked_correctly(): void
    {
        $stock = Stock::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 100,
            'batch_no' => 'REF-001',
        ]);

        $m1 = $this->service->addStock($stock, 50, 'App\\Models\\Purchase', 10, $this->user->id, 'PO-10');
        $m2 = $this->service->allocate($stock, 20, 'App\\Models\\Sale', 5, $this->user->id, 'Sale-5');
        $m3 = $this->service->release($stock, 5, 'App\\Models\\SaleReturn', 3, $this->user->id, 'Return-3');
        $m4 = $this->service->adjustStock($stock, 130, 'App\\Models\\StockAudit', 7, $this->user->id, 'Audit-7');

        // Verify reference types
        $this->assertEquals('App\\Models\\Purchase', $m1->reference_type);
        $this->assertEquals(10, $m1->reference_id);
        $this->assertEquals('PO-10', $m1->notes);

        $this->assertEquals('App\\Models\\Sale', $m2->reference_type);
        $this->assertEquals(5, $m2->reference_id);

        $this->assertEquals('App\\Models\\SaleReturn', $m3->reference_type);
        $this->assertEquals(3, $m3->reference_id);

        $this->assertEquals('App\\Models\\StockAudit', $m4->reference_type);
        $this->assertEquals(7, $m4->reference_id);
    }
}
