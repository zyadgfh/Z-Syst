<?php

namespace Tests\Integration;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferAudit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\WarehouseTransferServiceV2;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTransferIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private WarehouseTransferServiceV2 $transferService;
    private Business $business;
    private Product $product;
    private User $user;
    private Warehouse $warehouseA;
    private Warehouse $warehouseB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transferService = app(WarehouseTransferServiceV2::class);

        $businessCategory = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create([
            'business_category_id' => $businessCategory->id,
        ]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->product = Product::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->warehouseA = Warehouse::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $this->warehouseB = Warehouse::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Branch Warehouse',
            'code' => 'WH-BRANCH',
            'is_active' => true,
        ]);
    }

    // ── Create Transfer ────────────────────────────────────────────

    public function test_create_transfer_success(): void
    {
        // Seed stock in warehouse A
        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ]);

        $transfer = $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 30,
            'notes' => 'Restocking branch',
        ], $this->user->id);

        $this->assertNotNull($transfer);
        $this->assertEquals('pending', $transfer->status);
        $this->assertEquals(30, $transfer->quantity);

        // Audit trail should have 'created' entry
        $audit = StockTransferAudit::where('stock_transfer_id', $transfer->id)->first();
        $this->assertNotNull($audit);
        $this->assertEquals('created', $audit->action);
        $this->assertEquals('success', $audit->status);
        $this->assertEquals(100, $audit->from_stock_before);
        $this->assertEquals(0, $audit->to_stock_before);
    }

    public function test_create_transfer_rejects_same_warehouse(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('different');

        $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ], $this->user->id);
    }

    public function test_create_transfer_rejects_insufficient_stock(): void
    {
        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
        ], $this->user->id);
    }

    public function test_create_transfer_rejects_inactive_source_warehouse(): void
    {
        $this->warehouseA->update(['is_active' => false]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not active');

        $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ], $this->user->id);
    }

    public function test_create_transfer_rejects_inactive_dest_warehouse(): void
    {
        $this->warehouseB->update(['is_active' => false]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not active');

        $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ], $this->user->id);
    }

    public function test_create_transfer_rejects_zero_quantity(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('greater than 0');

        $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 0,
        ], $this->user->id);
    }

    // ── Complete Transfer ──────────────────────────────────────────

    public function test_complete_transfer_moves_stock(): void
    {
        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ]);

        $transfer = $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 40,
        ], $this->user->id);

        // Complete the transfer
        $completed = $this->transferService->completeTransfer($transfer, $this->user->id);

        $this->assertEquals('completed', $completed->status);

        // Verify stock moved
        $stockA = WarehouseStock::where([
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
        ])->first();

        $stockB = WarehouseStock::where([
            'warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
        ])->first();

        $this->assertEquals(60, $stockA->quantity);
        $this->assertEquals(40, $stockB->quantity);

        // Verify audit trail has both created + completed
        $audits = StockTransferAudit::where('stock_transfer_id', $transfer->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(2, $audits);
        $this->assertEquals('created', $audits[0]->action);
        $this->assertEquals('completed', $audits[1]->action);

        // Verify completion audit records stock before/after
        $completedAudit = $audits[1];
        $this->assertEquals(100, $completedAudit->from_stock_before);
        $this->assertEquals(60, $completedAudit->from_stock_after);
        $this->assertEquals(0, $completedAudit->to_stock_before);
        $this->assertEquals(40, $completedAudit->to_stock_after);
    }

    public function test_complete_transfer_fails_when_stock_depleted_since_creation(): void
    {
        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
        ]);

        $transfer = $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
        ], $this->user->id);

        // Stock gets depleted by another sale
        $stockA = WarehouseStock::where([
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
        ])->first();
        $stockA->update(['quantity' => 10]);

        // Complete should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('insufficient stock');

        $this->transferService->completeTransfer($transfer, $this->user->id);

        // Verify failure audit was recorded
        $failedAudit = StockTransferAudit::where('stock_transfer_id', $transfer->id)
            ->where('action', 'failed')
            ->first();

        $this->assertNotNull($failedAudit);
        $this->assertEquals('failed', $failedAudit->status);
    }

    public function test_complete_transfer_rejects_non_pending(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'status' => 'completed',
            'user_id' => $this->user->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not pending');

        $this->transferService->completeTransfer($transfer, $this->user->id);
    }

    // ── Cancel Transfer ────────────────────────────────────────────

    public function test_cancel_transfer_success(): void
    {
        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ]);

        $transfer = $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 30,
        ], $this->user->id);

        $cancelled = $this->transferService->cancelTransfer($transfer, $this->user->id, 'No longer needed');

        $this->assertEquals('cancelled', $cancelled->status);

        // Stock should remain unchanged
        $stockA = WarehouseStock::where([
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
        ])->first();

        $this->assertEquals(100, $stockA->quantity);

        // Audit trail should have created + cancelled
        $audits = StockTransferAudit::where('stock_transfer_id', $transfer->id)->get();
        $this->assertCount(2, $audits);
        $this->assertEquals('cancelled', $audits[1]->action);
        $this->assertEquals('No longer needed', $audits[1]->notes);
    }

    public function test_cancel_transfer_rejects_non_pending(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'status' => 'completed',
            'user_id' => $this->user->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be cancelled');

        $this->transferService->cancelTransfer($transfer, $this->user->id);
    }

    // ── Full Lifecycle ─────────────────────────────────────────────

    public function test_full_transfer_lifecycle(): void
    {
        // Setup: Warehouse A has 200 units
        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => 200,
        ]);

        // Step 1: Create transfer of 50 units
        $transfer = $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
            'notes' => 'Monthly restock',
        ], $this->user->id);

        $this->assertEquals('pending', $transfer->status);

        // Step 2: Complete the transfer
        $completed = $this->transferService->completeTransfer($transfer, $this->user->id);
        $this->assertEquals('completed', $completed->status);

        // Step 3: Verify stock
        $stockA = WarehouseStock::where([
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
        ])->first();
        $stockB = WarehouseStock::where([
            'warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
        ])->first();

        $this->assertEquals(150, $stockA->quantity);
        $this->assertEquals(50, $stockB->quantity);

        // Step 4: Create another transfer back (return 20 units)
        $returnTransfer = $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseB->id,
            'to_warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
            'notes' => 'Return excess',
        ], $this->user->id);

        $this->transferService->completeTransfer($returnTransfer, $this->user->id);

        // Final stock check
        $stockA->refresh();
        $stockB->refresh();
        $this->assertEquals(170, $stockA->quantity);
        $this->assertEquals(30, $stockB->quantity);

        // Verify full audit trail
        $auditsA = StockTransferAudit::where('stock_transfer_id', $transfer->id)->count();
        $auditsB = StockTransferAudit::where('stock_transfer_id', $returnTransfer->id)->count();
        $this->assertEquals(2, $auditsA); // created + completed
        $this->assertEquals(2, $auditsB); // created + completed
    }

    // ── Audit Trail ────────────────────────────────────────────────

    public function test_audit_trail_records_all_metadata(): void
    {
        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => 80,
        ]);

        $transfer = $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 25,
            'notes' => 'Emergency restock',
        ], $this->user->id);

        $this->transferService->completeTransfer($transfer, $this->user->id);

        $trail = $this->transferService->getAuditTrail($transfer->id);

        $this->assertCount(2, $trail);

        // Verify created audit
        $created = $trail->firstWhere('action', 'created');
        $this->assertNotNull($created);
        $this->assertEquals($this->user->id, $created->user_id);
        $this->assertEquals($this->warehouseA->id, $created->from_warehouse_id);
        $this->assertEquals($this->warehouseB->id, $created->to_warehouse_id);
        $this->assertEquals($this->product->id, $created->product_id);
        $this->assertEquals(25, $created->quantity);
        $this->assertEquals(80, $created->from_stock_before);
        $this->assertEquals(0, $created->to_stock_before);

        // Verify completed audit
        $completed = $trail->firstWhere('action', 'completed');
        $this->assertNotNull($completed);
        $this->assertEquals(80, $completed->from_stock_before);
        $this->assertEquals(55, $completed->from_stock_after);
        $this->assertEquals(0, $completed->to_stock_before);
        $this->assertEquals(25, $completed->to_stock_after);
    }

    public function test_failed_transfer_audit_records_error_metadata(): void
    {
        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => 30,
        ]);

        $transfer = $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 30,
        ], $this->user->id);

        // Deplete stock before completing
        WarehouseStock::where([
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
        ])->update(['quantity' => 5]);

        try {
            $this->transferService->completeTransfer($transfer, $this->user->id);
        } catch (\Exception $e) {
            // Expected
        }

        $failedAudit = StockTransferAudit::where('stock_transfer_id', $transfer->id)
            ->where('action', 'failed')
            ->first();

        $this->assertNotNull($failedAudit);
        $this->assertEquals('failed', $failedAudit->status);
        $this->assertEquals(5, $failedAudit->from_stock_before);
        $this->assertNotNull($failedAudit->metadata);
        $this->assertEquals('insufficient_stock', $failedAudit->metadata['error']);
    }

    // ── Multiple Products per Warehouse ────────────────────────────

    public function test_multiple_products_transfer_independently(): void
    {
        $product2 = Product::factory()->create(['business_id' => $this->business->id]);

        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ]);

        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $product2->id,
            'quantity' => 200,
        ]);

        // Transfer product 1
        $t1 = $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
            'quantity' => 30,
        ], $this->user->id);

        // Transfer product 2
        $t2 = $this->transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $product2->id,
            'quantity' => 50,
        ], $this->user->id);

        $this->transferService->completeTransfer($t1, $this->user->id);
        $this->transferService->completeTransfer($t2, $this->user->id);

        // Verify each product moved independently
        $stock1A = WarehouseStock::where([
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->product->id,
        ])->first();
        $stock1B = WarehouseStock::where([
            'warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->product->id,
        ])->first();
        $stock2A = WarehouseStock::where([
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $product2->id,
        ])->first();
        $stock2B = WarehouseStock::where([
            'warehouse_id' => $this->warehouseB->id,
            'product_id' => $product2->id,
        ])->first();

        $this->assertEquals(70, $stock1A->quantity);
        $this->assertEquals(30, $stock1B->quantity);
        $this->assertEquals(150, $stock2A->quantity);
        $this->assertEquals(50, $stock2B->quantity);
    }
}
