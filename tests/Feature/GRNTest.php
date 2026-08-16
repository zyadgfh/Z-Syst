<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GRNTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $grnService;
    protected $business;
    protected $user;
    protected $warehouse;
    protected $supplier;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grnService = app(\App\Services\GRNService::class);

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->warehouse = Warehouse::factory()->create(['business_id' => $this->business->id]);
        $this->supplier = \App\Models\Supplier::factory()->create(['business_id' => $this->business->id]);
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);
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
            'notes' => $this->faker->sentence,
        ], $overrides);

        return GoodsReceivedNote::create($data);
    }

    protected function createPOWithItems(): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'business_id' => $this->business->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'status' => 'accepted',
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addWeek()->toDateString(),
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => 0,
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 25.00,
            'total_price' => 250.00,
            'quantity_received' => 0,
        ]);

        $po->update(['subtotal' => 250.00, 'total_amount' => 250.00]);

        return $po;
    }

    // ─── CREATE ───────────────────────────────────────────────

    public function test_can_create_grn(): void
    {
        $data = [
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->createPOWithItems()->id,
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

        $grn = $this->grnService->create($data);

        $this->assertInstanceOf(GoodsReceivedNote::class, $grn);
        $this->assertEquals($this->business->id, $grn->business_id);
        $this->assertEquals(GoodsReceivedNote::STATUS_DRAFT, $grn->status);
        $this->assertEquals(1, $grn->items->count());
    }

    public function test_create_grn_validates_required_fields(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->grnService->create([]);
    }

    // ─── ADD ITEM ─────────────────────────────────────────────

    public function test_can_add_item_to_grn(): void
    {
        $grn = $this->createGRN();

        $item = $this->grnService->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $this->assertInstanceOf(GrnItem::class, $item);
        $this->assertEquals($this->product->id, $item->product_id);
        $this->assertEquals(5, $item->quantity_received);
    }

    public function test_add_item_throws_on_invalid_product(): void
    {
        $grn = $this->createGRN();

        $this->expectException(\Exception::class);

        $this->grnService->addItem($grn, [
            'product_id' => 99999,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);
    }

    // ─── UPDATE ITEM ──────────────────────────────────────────

    public function test_can_update_grn_item(): void
    {
        $grn = $this->createGRN();
        $item = $this->grnService->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $updated = $this->grnService->updateItem($item, [
            'quantity_received' => 8,
        ]);

        $this->assertEquals(8, $updated->quantity_received);
    }

    public function test_cannot_update_item_on_non_draft_grn(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $item = $this->grnService->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $this->expectException(\Exception::class);
        $this->grnService->updateItem($item, ['quantity_received' => 8]);
    }

    // ─── REMOVE ITEM ──────────────────────────────────────────

    public function test_can_remove_grn_item(): void
    {
        $grn = $this->createGRN();
        $item = $this->grnService->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $result = $this->grnService->removeItem($item);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('grn_items', ['id' => $item->id]);
    }

    // ─── STATUS WORKFLOW ──────────────────────────────────────

    public function test_can_verify_grn(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->grnService->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $verified = $this->grnService->verify($grn, [
            'verification_data' => [
                [
                    'product_id' => $this->product->id,
                    'is_accepted' => true,
                    'quantity_accepted' => 5,
                    'notes' => 'Good condition',
                ],
            ],
        ]);

        $this->assertEquals(GoodsReceivedNote::STATUS_VERIFIED, $verified->status);
    }

    public function test_can_accept_grn(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->grnService->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $accepted = $this->grnService->accept($grn);

        $this->assertEquals(GoodsReceivedNote::STATUS_ACCEPTED, $accepted->status);
    }

    public function test_can_reject_grn(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->grnService->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $rejected = $this->grnService->reject($grn);

        $this->assertEquals(GoodsReceivedNote::STATUS_REJECTED, $rejected->status);
    }

    public function test_cannot_verify_non_pending_grn(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);

        $this->expectException(\Exception::class);
        $this->grnService->verify($grn, []);
    }

    public function test_cannot_accept_non_verified_grn(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);

        $this->expectException(\Exception::class);
        $this->grnService->accept($grn);
    }

    public function test_cannot_reject_draft_grn(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);

        $this->expectException(\Exception::class);
        $this->grnService->reject($grn);
    }

    // ─── CAN BE EDITED / DELETED / VERIFIED / ACCEPTED ────────

    public function test_can_be_edited_when_draft(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);
        $this->assertTrue($this->grnService->canBeEdited($grn));
    }

    public function test_can_be_edited_when_pending(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->assertTrue($this->grnService->canBeEdited($grn));
    }

    public function test_cannot_be_edited_when_verified(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->assertFalse($this->grnService->canBeEdited($grn));
    }

    public function test_can_be_deleted_when_draft(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);
        $this->assertTrue($this->grnService->canBeDeleted($grn));
    }

    public function test_cannot_be_deleted_when_pending(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->assertFalse($this->grnService->canBeDeleted($grn));
    }

    public function test_can_be_verified_when_pending(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->assertTrue($this->grnService->canBeVerified($grn));
    }

    public function test_cannot_be_verified_when_draft(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);
        $this->assertFalse($this->grnService->canBeVerified($grn));
    }

    public function test_can_be_accepted_when_verified(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->assertTrue($this->grnService->canBeAccepted($grn));
    }

    public function test_cannot_be_accepted_when_pending(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->assertFalse($this->grnService->canBeAccepted($grn));
    }

    // ─── GETTERS ──────────────────────────────────────────────

    public function test_get_pending_grns(): void
    {
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);

        $pending = $this->grnService->getPendingGRNs($this->business->id);

        $this->assertCount(2, $pending);
    }

    public function test_get_by_status(): void
    {
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_REJECTED]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_DRAFT]);

        $verified = $this->grnService->getByStatus($this->business->id, GoodsReceivedNote::STATUS_VERIFIED);

        $this->assertCount(1, $verified);
    }

    public function test_get_statistics(): void
    {
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_REJECTED]);

        $stats = $this->grnService->getStatistics($this->business->id);

        $this->assertArrayHasKey('pending_count', $stats);
        $this->assertArrayHasKey('verified_count', $stats);
        $this->assertArrayHasKey('rejected_count', $stats);
        $this->assertEquals(1, $stats['pending_count']);
        $this->assertEquals(1, $stats['verified_count']);
        $this->assertEquals(1, $stats['rejected_count']);
    }

    // ─── UPDATE GRN STATUS (derived from items) ───────────────

    public function test_update_grn_status_derives_from_items(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);

        $this->grnService->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $status = $this->grnService->updateGRNStatus($grn);

        $this->assertContains($status, [
            GoodsReceivedNote::STATUS_PENDING,
            GoodsReceivedNote::STATUS_PARTIALLY_RECEIVED,
            GoodsReceivedNote::STATUS_RECEIVED,
        ]);
    }

    // ─── STOCK UPDATE ─────────────────────────────────────────

    public function test_accept_updates_stock(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->grnService->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $this->grnService->accept($grn);

        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
        ]);
    }

    // ─── SOFT DELETES ─────────────────────────────────────────

    public function test_grn_soft_deletes(): void
    {
        $grn = $this->createGRN();

        $grn->delete();

        $this->assertSoftDeleted('goods_received_notes', ['id' => $grn->id]);
    }

    // ─── RELATIONSHIPS ────────────────────────────────────────

    public function test_grn_belongs_to_warehouse(): void
    {
        $grn = $this->createGRN();

        $this->assertNotNull($grn->warehouse);
        $this->assertEquals($this->warehouse->id, $grn->warehouse->id);
    }

    public function test_grn_belongs_to_supplier(): void
    {
        $grn = $this->createGRN();

        $this->assertNotNull($grn->supplier);
        $this->assertEquals($this->supplier->id, $grn->supplier->id);
    }

    public function test_grn_has_many_items(): void
    {
        $grn = $this->createGRN();
        $this->grnService->addItem($grn, [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_price' => 25.00,
        ]);

        $this->assertEquals(1, $grn->items->count());
    }

    public function test_grn_belongs_to_user(): void
    {
        $grn = $this->createGRN();

        $this->assertNotNull($grn->user);
        $this->assertEquals($this->user->id, $grn->user->id);
    }
}
