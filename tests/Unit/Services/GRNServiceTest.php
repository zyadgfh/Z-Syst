<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GRNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class GRNServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $business;
    protected $user;
    protected $warehouse;
    protected $supplier;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->warehouse = Warehouse::factory()->create(['business_id' => $this->business->id]);
        $this->supplier = Supplier::factory()->create(['business_id' => $this->business->id]);
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);

        Auth::login($this->user);

        $this->service = new GRNService();
    }

    protected function createGRN(array $overrides = []): GoodsReceivedNote
    {
        $data = array_merge([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'status' => GoodsReceivedNote::STATUS_DRAFT,
            'received_date' => now()->toDateString(),
            'notes' => 'Test GRN',
        ], $overrides);

        return GoodsReceivedNote::create($data);
    }

    // ─── CREATE ───────────────────────────────────────────────

    public function test_create_creates_grn_and_items(): void
    {
        $data = [
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'status' => GoodsReceivedNote::STATUS_DRAFT,
            'received_date' => now()->toDateString(),
            'notes' => 'Test GRN',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 10,
                    'quantity_received' => 5,
                    'unit_price' => 25.00,
                ],
            ],
        ];

        $grn = $this->service->create($data);

        $this->assertDatabaseHas('goods_received_notes', ['id' => $grn->id]);
        $this->assertDatabaseHas('grn_items', ['goods_received_note_id' => $grn->id]);
    }

    public function test_create_returns_goods_received_note_instance(): void
    {
        $grn = $this->service->create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'status' => GoodsReceivedNote::STATUS_DRAFT,
            'received_date' => now()->toDateString(),
        ]);

        $this->assertInstanceOf(GoodsReceivedNote::class, $grn);
    }

    // ─── ADD ITEM ─────────────────────────────────────────────

    public function test_add_item_creates_grn_item(): void
    {
        $grn = $this->createGRN();

        $item = $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $this->assertInstanceOf(GrnItem::class, $item);
        $this->assertDatabaseHas('grn_items', ['id' => $item->id]);
    }

    public function test_add_item_sets_correct_values(): void
    {
        $grn = $this->createGRN();

        $item = $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $this->assertEquals($this->product->id, $item->product_id);
        $this->assertEquals(10, $item->quantity_ordered);
        $this->assertEquals(5, $item->quantity_received);
        $this->assertEquals(25.00, $item->unit_price);
    }

    // ─── UPDATE ITEM ──────────────────────────────────────────

    public function test_update_item_modifies_quantity(): void
    {
        $grn = $this->createGRN();
        $item = $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $updated = $this->service->updateItem($item, ['quantity_received' => 8]);

        $this->assertEquals(8, $updated->fresh()->quantity_received);
    }

    // ─── REMOVE ITEM ──────────────────────────────────────────

    public function test_remove_item_deletes_grn_item(): void
    {
        $grn = $this->createGRN();
        $item = $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $result = $this->service->removeItem($item);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('grn_items', ['id' => $item->id]);
    }

    // ─── VERIFY ───────────────────────────────────────────────

    public function test_verify_changes_status_to_verified(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $verified = $this->service->verify($grn, [
            'verification_data' => [
                [
                    'product_id' => $this->product->id,
                    'is_accepted' => true,
                    'quantity_accepted' => 5,
                    'notes' => 'Accepted',
                ],
            ],
        ]);

        $this->assertEquals(GoodsReceivedNote::STATUS_VERIFIED, $verified->fresh()->status);
    }

    public function test_verify_creates_quality_checks(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $this->service->verify($grn, [
            'verification_data' => [
                [
                    'product_id' => $this->product->id,
                    'is_accepted' => true,
                    'quantity_accepted' => 5,
                    'notes' => 'Good',
                ],
            ],
        ]);

        $this->assertDatabaseHas('quality_checks', [
            'goods_received_note_id' => $grn->id,
        ]);
    }

    // ─── ACCEPT ───────────────────────────────────────────────

    public function test_accept_changes_status_to_accepted(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $accepted = $this->service->accept($grn);

        $this->assertEquals(GoodsReceivedNote::STATUS_ACCEPTED, $accepted->fresh()->status);
    }

    // ─── REJECT ───────────────────────────────────────────────

    public function test_reject_changes_status_to_rejected(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $rejected = $this->service->reject($grn);

        $this->assertEquals(GoodsReceivedNote::STATUS_REJECTED, $rejected->fresh()->status);
    }

    // ─── STATUS GUARDS ────────────────────────────────────────

    public function test_verify_throws_on_wrong_status(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);

        $this->expectException(\Exception::class);
        $this->service->verify($grn, []);
    }

    public function test_accept_throws_on_wrong_status(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);

        $this->expectException(\Exception::class);
        $this->service->accept($grn);
    }

    public function test_reject_throws_on_wrong_status(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);

        $this->expectException(\Exception::class);
        $this->service->reject($grn);
    }

    // ─── CAN BE EDITED / DELETED / VERIFIED / ACCEPTED ────────

    public function test_can_be_edited_draft(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);
        $this->assertTrue($this->service->canBeEdited($grn));
    }

    public function test_can_be_edited_pending(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->assertTrue($this->service->canBeEdited($grn));
    }

    public function test_cannot_be_edited_verified(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->assertFalse($this->service->canBeEdited($grn));
    }

    public function test_can_be_deleted_draft(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);
        $this->assertTrue($this->service->canBeDeleted($grn));
    }

    public function test_cannot_be_deleted_pending(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->assertFalse($this->service->canBeDeleted($grn));
    }

    public function test_can_be_verified_pending(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->assertTrue($this->service->canBeVerified($grn));
    }

    public function test_cannot_be_verified_draft(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);
        $this->assertFalse($this->service->canBeVerified($grn));
    }

    public function test_can_be_accepted_verified(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->assertTrue($this->service->canBeAccepted($grn));
    }

    public function test_cannot_be_accepted_pending(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->assertFalse($this->service->canBeAccepted($grn));
    }

    // ─── GETTERS ──────────────────────────────────────────────

    public function test_get_pending_grns_filters_correctly(): void
    {
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);

        $result = $this->service->getPendingGRNs($this->business->id);

        $this->assertCount(2, $result);
    }

    public function test_get_by_status_filters_correctly(): void
    {
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_REJECTED]);

        $verified = $this->service->getByStatus($this->business->id, GoodsReceivedNote::STATUS_VERIFIED);
        $rejected = $this->service->getByStatus($this->business->id, GoodsReceivedNote::STATUS_REJECTED);

        $this->assertCount(1, $verified);
        $this->assertCount(1, $rejected);
    }

    public function test_get_statistics_returns_counts(): void
    {
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);

        $stats = $this->service->getStatistics($this->business->id);

        $this->assertEquals(2, $stats['pending_count']);
        $this->assertEquals(1, $stats['verified_count']);
    }

    // ─── UPDATE GRN STATUS ────────────────────────────────────

    public function test_update_grn_status_returns_valid_status(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $status = $this->service->updateGRNStatus($grn);

        $this->assertContains($status, [
            GoodsReceivedNote::STATUS_PENDING,
            GoodsReceivedNote::STATUS_PARTIALLY_RECEIVED,
            GoodsReceivedNote::STATUS_RECEIVED,
        ]);
    }

    // ─── UPDATE STOCK FROM PO ─────────────────────────────────

    public function test_update_stock_from_po_updates_product_stock(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $this->service->updateStockFromPO($grn);

        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
        ]);
    }
}
