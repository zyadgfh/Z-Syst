<?php

namespace Tests\Unit;

use App\Events\StockTransferApproved;
use App\Events\StockTransferCancelled;
use App\Events\StockTransferReceived;
use App\Events\StockTransferRejected;
use App\Events\StockTransferShipped;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\StockTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * StockTransferServiceTest
 *
 * Unit tests for the StockTransferService.
 * Tests the business logic for stock transfer operations.
 */
class StockTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockTransferService $stockTransferService;

    protected Company $company;

    protected Branch $fromBranch;

    protected Branch $toBranch;

    protected User $user;

    protected Product $product;

    protected ProductStock $productStock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockTransferService = new StockTransferService;

        // Create test data
        $this->company = Company::factory()->create();
        $this->fromBranch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->toBranch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->fromBranch->id,
        ]);
        $this->product = Product::factory()->create(['company_id' => $this->company->id]);
        $this->productStock = ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'branch_id' => $this->fromBranch->id,
            'quantity' => 100,
        ]);

        // Authenticate the user
        $this->actingAs($this->user);
    }

    /**
     * Test creating a stock transfer.
     */
    public function test_create_transfer(): void
    {
        $data = [
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'notes' => 'Test transfer',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_stock_id' => $this->productStock->id,
                    'quantity_requested' => 50,
                    'batch_number' => 'BATCH001',
                    'expiry_date' => now()->addMonths(6)->format('Y-m-d'),
                    'unit_cost' => 10.00,
                    'notes' => 'Test item',
                ],
            ],
        ];

        $transfer = $this->stockTransferService->createTransfer($data, $this->user->id);

        $this->assertInstanceOf(StockTransfer::class, $transfer);
        $this->assertEquals('pending', $transfer->status);
        $this->assertEquals($this->company->id, $transfer->company_id);
        $this->assertEquals($this->fromBranch->id, $transfer->from_branch_id);
        $this->assertEquals($this->toBranch->id, $transfer->to_branch_id);
        $this->assertEquals($this->user->id, $transfer->requested_by);
        $this->assertEquals(1, $transfer->total_items);
        $this->assertEquals(50, $transfer->total_quantity);
        $this->assertEquals(500.00, $transfer->total_value);
        $this->assertNotNull($transfer->transfer_number);
        $this->assertCount(1, $transfer->items);
    }

    /**
     * Test approving a stock transfer.
     */
    public function test_approve_transfer(): void
    {
        Event::fake();

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $approvedTransfer = $this->stockTransferService->approveTransfer($transfer, $this->user->id);

        $this->assertEquals('approved', $approvedTransfer->status);
        $this->assertEquals($this->user->id, $approvedTransfer->approved_by);
        $this->assertNotNull($approvedTransfer->approved_at);

        Event::assertDispatched(StockTransferApproved::class);
    }

    /**
     * Test rejecting a stock transfer.
     */
    public function test_reject_transfer(): void
    {
        Event::fake();

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $rejectedTransfer = $this->stockTransferService->rejectTransfer(
            $transfer,
            $this->user->id,
            'Insufficient stock'
        );

        $this->assertEquals('rejected', $rejectedTransfer->status);
        $this->assertEquals($this->user->id, $rejectedTransfer->approved_by);
        $this->assertEquals('Insufficient stock', $rejectedTransfer->rejection_reason);
        $this->assertNotNull($rejectedTransfer->rejected_at);

        Event::assertDispatched(StockTransferRejected::class);
    }

    /**
     * Test shipping a stock transfer.
     */
    public function test_ship_transfer(): void
    {
        Event::fake();

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'requested_by' => $this->user->id,
            'status' => 'approved',
        ]);

        $transferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'product_stock_id' => $this->productStock->id,
            'quantity_requested' => 50,
            'quantity_sent' => 0,
            'quantity_received' => 0,
            'unit_cost' => 10.00,
            'total_cost' => 500.00,
        ]);

        $itemsData = [
            [
                'id' => $transferItem->id,
                'quantity_sent' => 50,
                'batch_number' => 'BATCH001',
                'expiry_date' => now()->addMonths(6)->format('Y-m-d'),
            ],
        ];

        $shippedTransfer = $this->stockTransferService->shipTransfer($transfer, $this->user->id, $itemsData);

        $this->assertEquals('in_transit', $shippedTransfer->status);
        $this->assertEquals($this->user->id, $shippedTransfer->shipped_by);
        $this->assertNotNull($shippedTransfer->shipped_at);
        $this->assertEquals(50, $transferItem->fresh()->quantity_sent);

        // Verify stock was deducted
        $this->assertEquals(50, $this->productStock->fresh()->quantity);

        Event::assertDispatched(StockTransferShipped::class);
    }

    /**
     * Test receiving a stock transfer.
     */
    public function test_receive_transfer(): void
    {
        Event::fake();

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'requested_by' => $this->user->id,
            'status' => 'in_transit',
        ]);

        $transferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'product_stock_id' => $this->productStock->id,
            'quantity_requested' => 50,
            'quantity_sent' => 50,
            'quantity_received' => 0,
            'unit_cost' => 10.00,
            'total_cost' => 500.00,
            'batch_number' => 'BATCH001',
            'expiry_date' => now()->addMonths(6),
        ]);

        $itemsData = [
            [
                'id' => $transferItem->id,
                'quantity_received' => 50,
            ],
        ];

        $receivedTransfer = $this->stockTransferService->receiveTransfer($transfer, $this->user->id, $itemsData);

        $this->assertEquals('received', $receivedTransfer->status);
        $this->assertEquals($this->user->id, $receivedTransfer->received_by);
        $this->assertNotNull($receivedTransfer->received_at);
        $this->assertEquals(50, $transferItem->fresh()->quantity_received);

        // Verify stock was added to destination branch
        $destinationStock = ProductStock::where('product_id', $this->product->id)
            ->where('branch_id', $this->toBranch->id)
            ->first();

        $this->assertNotNull($destinationStock);
        $this->assertEquals(50, $destinationStock->quantity);

        Event::assertDispatched(StockTransferReceived::class);
    }

    /**
     * Test cancelling a stock transfer.
     */
    public function test_cancel_transfer(): void
    {
        Event::fake();

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $cancelledTransfer = $this->stockTransferService->cancelTransfer($transfer, $this->user->id, 'No longer needed');

        $this->assertEquals('cancelled', $cancelledTransfer->status);
        $this->assertNotNull($cancelledTransfer->cancelled_at);

        Event::assertDispatched(StockTransferCancelled::class);
    }

    /**
     * Test cancelling an in-transit transfer restores stock.
     */
    public function test_cancel_in_transit_transfer_restores_stock(): void
    {
        Event::fake();

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'requested_by' => $this->user->id,
            'status' => 'in_transit',
        ]);

        $transferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'product_stock_id' => $this->productStock->id,
            'quantity_requested' => 50,
            'quantity_sent' => 50,
            'quantity_received' => 0,
            'unit_cost' => 10.00,
            'total_cost' => 500.00,
            'batch_number' => 'BATCH001',
        ]);

        // Deduct stock (simulating shipping)
        $this->productStock->decrement('quantity', 50);
        $this->assertEquals(50, $this->productStock->quantity);

        $cancelledTransfer = $this->stockTransferService->cancelTransfer($transfer, $this->user->id);

        $this->assertEquals('cancelled', $cancelledTransfer->status);

        // Verify stock was restored
        $this->assertEquals(100, $this->productStock->fresh()->quantity);

        Event::assertDispatched(StockTransferCancelled::class);
    }

    /**
     * Test getting transfers with filters.
     */
    public function test_get_transfers_with_filters(): void
    {
        StockTransfer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'pending',
        ]);

        StockTransfer::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'received',
        ]);

        $filters = ['status' => 'pending'];
        $transfers = $this->stockTransferService->getTransfers($this->company->id, $filters);

        $this->assertCount(3, $transfers);
        $this->assertEquals('pending', $transfers->first()->status);
    }

    /**
     * Test getting transfer statistics.
     */
    public function test_get_transfer_statistics(): void
    {
        StockTransfer::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'pending',
        ]);

        StockTransfer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'received',
            'total_value' => 1000.00,
            'total_quantity' => 100,
        ]);

        $statistics = $this->stockTransferService->getTransferStatistics($this->company->id);

        $this->assertEquals(8, $statistics['total_transfers']);
        $this->assertEquals(5, $statistics['pending_transfers']);
        $this->assertEquals(3, $statistics['received_transfers']);
        $this->assertEquals(3000.00, $statistics['total_value_transferred']);
        $this->assertEquals(300, $statistics['total_quantity_transferred']);
    }

    /**
     * Test that approval fails for non-pending transfer.
     */
    public function test_approve_non_pending_transfer_fails(): void
    {
        $this->expectException(\Exception::class);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'requested_by' => $this->user->id,
            'status' => 'approved',
        ]);

        $this->stockTransferService->approveTransfer($transfer, $this->user->id);
    }

    /**
     * Test that shipping fails for non-approved transfer.
     */
    public function test_ship_non_approved_transfer_fails(): void
    {
        $this->expectException(\Exception::class);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->stockTransferService->shipTransfer($transfer, $this->user->id, []);
    }

    /**
     * Test that receiving fails for non-in-transit transfer.
     */
    public function test_receive_non_in_transit_transfer_fails(): void
    {
        $this->expectException(\Exception::class);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'requested_by' => $this->user->id,
            'status' => 'approved',
        ]);

        $this->stockTransferService->receiveTransfer($transfer, $this->user->id, []);
    }
}
