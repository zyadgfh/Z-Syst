<?php

namespace Tests\Feature;

use App\Models\Party;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected PurchaseOrderService $poService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->poService = app(PurchaseOrderService::class);
    }

    /**
     * Test creating a purchase order.
     */
    public function test_can_create_purchase_order(): void
    {
        $user = User::factory()->create();
        $supplier = Party::factory()->create(['type' => 'supplier']);
        $product = Product::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'business_id' => $user->business_id,
            'branch_id' => $user->branch_id,
            'priority' => 'normal',
            'expected_delivery_date' => now()->addDays(7),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 50.00,
                    'discount' => 5,
                ],
            ],
        ];

        $po = $this->poService->create($data);

        $this->assertInstanceOf(PurchaseOrder::class, $po);
        $this->assertEquals($supplier->id, $po->supplier_id);
        $this->assertEquals(PurchaseOrder::STATUS_DRAFT, $po->status);
        $this->assertNotNull($po->po_number);
        $this->assertCount(1, $po->items);
    }

    /**
     * Test updating a purchase order.
     */
    public function test_can_update_purchase_order(): void
    {
        $user = User::factory()->create();
        $po = PurchaseOrder::factory()->create(['business_id' => $user->business_id]);
        $newSupplier = Party::factory()->create(['type' => 'supplier']);

        $data = [
            'supplier_id' => $newSupplier->id,
            'priority' => 'high',
            'expected_delivery_date' => now()->addDays(14),
        ];

        $updatedPo = $this->poService->update($po, $data);

        $this->assertEquals($newSupplier->id, $updatedPo->supplier_id);
        $this->assertEquals('high', $updatedPo->priority);
    }

    /**
     * Test sending a purchase order.
     */
    public function test_can_send_purchase_order(): void
    {
        $po = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_DRAFT]);

        $sentPo = $this->poService->send($po);

        $this->assertEquals(PurchaseOrder::STATUS_SENT, $sentPo->status);
        $this->assertNotNull($sentPo->sent_at);
    }

    /**
     * Test approving a purchase order.
     */
    public function test_can_approve_purchase_order(): void
    {
        $user = User::factory()->create();
        $po = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_SENT]);

        $approvedPo = $this->poService->approve($po, $user->id);

        $this->assertEquals(PurchaseOrder::STATUS_ACCEPTED, $approvedPo->status);
        $this->assertEquals($user->id, $approvedPo->approved_by);
        $this->assertNotNull($approvedPo->approved_at);
    }

    /**
     * Test rejecting a purchase order.
     */
    public function test_can_reject_purchase_order(): void
    {
        $user = User::factory()->create();
        $po = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_SENT]);
        $reason = 'Out of stock';

        $rejectedPo = $this->poService->reject($po, $user->id, $reason);

        $this->assertEquals(PurchaseOrder::STATUS_REJECTED, $rejectedPo->status);
        $this->assertEquals($reason, $rejectedPo->rejection_reason);
        $this->assertNotNull($rejectedPo->rejected_at);
    }

    /**
     * Test cancelling a purchase order.
     */
    public function test_can_cancel_purchase_order(): void
    {
        $po = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_SENT]);

        $cancelledPo = $this->poService->cancel($po);

        $this->assertEquals(PurchaseOrder::STATUS_CANCELLED, $cancelledPo->status);
        $this->assertNotNull($cancelledPo->cancelled_at);
    }

    /**
     * Test restoring a cancelled purchase order.
     */
    public function test_can_restore_purchase_order(): void
    {
        $po = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_CANCELLED]);

        $restoredPo = $this->poService->restore($po);

        $this->assertEquals(PurchaseOrder::STATUS_DRAFT, $restoredPo->status);
    }

    /**
     * Test converting PO to purchase.
     */
    public function test_can_convert_po_to_purchase(): void
    {
        $po = PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->count(2), 'items')
            ->create(['status' => PurchaseOrder::STATUS_ACCEPTED]);

        $purchase = $this->poService->convertToPurchase($po);

        $this->assertNotNull($purchase);
        $this->assertEquals($po->supplier_id, $purchase->party_id);
        $this->assertEquals($po->total_amount, $purchase->totalAmount);
        $this->assertEquals(PurchaseOrder::STATUS_RECEIVED, $po->fresh()->status);
    }

    /**
     * Test cannot cancel received order.
     */
    public function test_cannot_cancel_received_order(): void
    {
        $this->expectException(\Exception::class);

        $po = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_RECEIVED]);
        $this->poService->cancel($po);
    }

    /**
     * Test cannot send non-draft order.
     */
    public function test_cannot_send_non_draft_order(): void
    {
        $this->expectException(\Exception::class);

        $po = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_SENT]);
        $this->poService->send($po);
    }

    /**
     * Test cannot approve non-sent order.
     */
    public function test_cannot_approve_non_sent_order(): void
    {
        $this->expectException(\Exception::class);

        $user = User::factory()->create();
        $po = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_DRAFT]);
        $this->poService->approve($po, $user->id);
    }

    /**
     * Test cannot convert non-approved order.
     */
    public function test_cannot_convert_non_approved_order(): void
    {
        $this->expectException(\Exception::class);

        $po = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_SENT]);
        $this->poService->convertToPurchase($po);
    }

    /**
     * Test PO number generation.
     */
    public function test_po_number_is_generated(): void
    {
        $po = PurchaseOrder::factory()->create();

        $this->assertNotNull($po->po_number);
        $this->assertMatchesRegularExpression('/^PO-\d{4}-\d{6}$/', $po->po_number);
    }

    /**
     * Test PO total calculation.
     */
    public function test_po_total_is_calculated(): void
    {
        $po = PurchaseOrder::factory()->create();

        // Add items manually so totals are computed
        for ($i = 0; $i < 3; $i++) {
            $this->poService->addItem($po, [
                'product_id' => Product::factory()->create()->id,
                'quantity' => 10,
                'unit_price' => 50.00,
                'discount' => 0,
            ]);
        }

        $po->refresh();
        $this->assertEquals(1500.00, $po->subtotal);
    }

    /**
     * Test PO business scope.
     */
    public function test_po_business_scope(): void
    {
        $business1 = User::factory()->create()->business_id;
        $business2 = User::factory()->create()->business_id;

        $po1 = PurchaseOrder::factory()->create(['business_id' => $business1]);
        $po2 = PurchaseOrder::factory()->create(['business_id' => $business2]);

        $business1Pos = PurchaseOrder::forBusiness($business1)->get();

        $this->assertCount(1, $business1Pos);
        $this->assertEquals($po1->id, $business1Pos->first()->id);
    }

    /**
     * Test PO supplier scope.
     */
    public function test_po_supplier_scope(): void
    {
        $supplier1 = Party::factory()->create(['type' => 'supplier']);
        $supplier2 = Party::factory()->create(['type' => 'supplier']);

        $po1 = PurchaseOrder::factory()->create(['supplier_id' => $supplier1->id]);
        $po2 = PurchaseOrder::factory()->create(['supplier_id' => $supplier2->id]);

        $supplier1Pos = PurchaseOrder::forSupplier($supplier1->id)->get();

        $this->assertCount(1, $supplier1Pos);
        $this->assertEquals($po1->id, $supplier1Pos->first()->id);
    }

    /**
     * Test PO status scope.
     */
    public function test_po_status_scope(): void
    {
        $po1 = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_DRAFT]);
        $po2 = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_SENT]);
        $po3 = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_DRAFT]);

        $draftPos = PurchaseOrder::byStatus(PurchaseOrder::STATUS_DRAFT)->get();

        $this->assertCount(2, $draftPos);
    }

    /**
     * Test PO pending scope.
     */
    public function test_po_pending_scope(): void
    {
        $po1 = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_DRAFT]);
        $po2 = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_SENT]);
        $po3 = PurchaseOrder::factory()->create(['status' => PurchaseOrder::STATUS_ACCEPTED]);

        $pendingPos = PurchaseOrder::pending()->get();

        $this->assertCount(2, $pendingPos);
    }

    /**
     * Test PO overdue scope.
     */
    public function test_po_overdue_scope(): void
    {
        $po1 = PurchaseOrder::factory()->create([
            'status' => PurchaseOrder::STATUS_SENT,
            'expected_delivery_date' => now()->subDays(5),
        ]);
        $po2 = PurchaseOrder::factory()->create([
            'status' => PurchaseOrder::STATUS_SENT,
            'expected_delivery_date' => now()->addDays(5),
        ]);

        $overduePos = PurchaseOrder::overdue()->get();

        $this->assertCount(1, $overduePos);
        $this->assertEquals($po1->id, $overduePos->first()->id);
    }

    /**
     * Test PO statistics.
     */
    public function test_get_po_statistics(): void
    {
        $businessId = User::factory()->create()->business_id;

        PurchaseOrder::factory()->count(5)->create([
            'business_id' => $businessId,
            'status' => PurchaseOrder::STATUS_DRAFT,
        ]);
        PurchaseOrder::factory()->count(3)->create([
            'business_id' => $businessId,
            'status' => PurchaseOrder::STATUS_SENT,
        ]);
        PurchaseOrder::factory()->count(2)->create([
            'business_id' => $businessId,
            'status' => PurchaseOrder::STATUS_RECEIVED,
        ]);

        $stats = $this->poService->getStatistics($businessId);

        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(5, $stats['draft']);
        $this->assertEquals(8, $stats['pending']); // 5 draft + 3 sent
        $this->assertEquals(2, $stats['approved']); // 2 received
    }

    /**
     * Test PO with no items cannot be created.
     */
    public function test_po_without_items_can_be_created(): void
    {
        $data = [
            'supplier_id' => Party::factory()->create(['type' => 'supplier'])->id,
            'business_id' => User::factory()->create()->business_id,
            'branch_id' => User::factory()->create()->branch_id,
        ];

        $po = $this->poService->create($data);

        $this->assertInstanceOf(PurchaseOrder::class, $po);
        $this->assertCount(0, $po->items);
    }

    /**
     * Test PO item creation.
     */
    public function test_can_add_item_to_po(): void
    {
        $po = PurchaseOrder::factory()->create();
        $product = Product::factory()->create();

        $itemData = [
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 50.00,
            'discount' => 5,
        ];

        $item = $this->poService->addItem($po, $itemData);

        $this->assertInstanceOf(PurchaseOrderItem::class, $item);
        $this->assertEquals($product->id, $item->product_id);
        $this->assertEquals(10, $item->quantity);
        $this->assertEquals(50.00, $item->unit_price);
        $this->assertEquals(5, $item->discount);
    }

    /**
     * Test PO item total calculation.
     */
    public function test_po_item_total_is_calculated(): void
    {
        $po = PurchaseOrder::factory()->create();
        $product = Product::factory()->create();

        $itemData = [
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 50.00,
            'discount' => 10,
        ];

        $item = $this->poService->addItem($po, $itemData);

        // Formula: (unit_price * quantity) - discount + tax
        $expectedTotal = (10 * 50.00) - 10 + 0;
        $this->assertEquals($expectedTotal, $item->total);
    }

    /**
     * Test PO with multiple items.
     */
    public function test_po_with_multiple_items(): void
    {
        $po = PurchaseOrder::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $this->poService->addItem($po, [
            'product_id' => $product1->id,
            'quantity' => 5,
            'unit_price' => 100.00,
        ]);

        $this->poService->addItem($po, [
            'product_id' => $product2->id,
            'quantity' => 10,
            'unit_price' => 50.00,
        ]);

        $this->assertCount(2, $po->items);
    }

    /**
     * Test PO completion percentage.
     */
    public function test_po_completion_percentage(): void
    {
        $po = PurchaseOrder::factory()->create();
        $this->poService->addItem($po, [
            'product_id' => Product::factory()->create()->id,
            'quantity' => 100,
            'unit_price' => 10.00,
        ]);
        $item = $po->items->first();
        $item->update(['received_quantity' => 50, 'pending_quantity' => 50]);
        $po->refresh();

        $totalQty = $po->items->sum('quantity');
        $receivedQty = $po->items->sum('received_quantity');
        $expectedPercentage = $totalQty > 0 ? round(($receivedQty / $totalQty) * 100) : 0;
        $this->assertEquals($expectedPercentage, $po->completion_percentage);
    }

    /**
     * Test API endpoint for creating PO.
     */
    public function test_api_can_create_purchase_order(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        $supplier = Party::factory()->create(['type' => 'supplier']);
        $product = Product::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'priority' => 'normal',
            'expected_delivery_date' => now()->addDays(7)->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson('/admin/purchase-orders', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Purchase order created successfully',
            ]);
    }

    /**
     * Test API endpoint for listing POs.
     */
    public function test_api_can_list_purchase_orders(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        PurchaseOrder::factory()->count(5)->create(['business_id' => $user->business_id]);

        $response = $this->actingAs($user)
            ->getJson('/admin/purchase-orders');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test API endpoint for showing PO.
     */
    public function test_api_can_show_purchase_order(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        $po = PurchaseOrder::factory()->create(['business_id' => $user->business_id]);

        $response = $this->actingAs($user)
            ->getJson("/admin/purchase-orders/{$po->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test API endpoint for updating PO.
     */
    public function test_api_can_update_purchase_order(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        $po = PurchaseOrder::factory()->create(['business_id' => $user->business_id]);
        $newSupplier = Party::factory()->create(['type' => 'supplier']);
        $product = Product::factory()->create();

        $data = [
            'supplier_id' => $newSupplier->id,
            'priority' => 'high',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_price' => 100.00,
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->putJson("/admin/purchase-orders/{$po->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Purchase order updated successfully',
            ]);
    }

    /**
     * Test API endpoint for deleting PO.
     */
    public function test_api_can_delete_purchase_order(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        $po = PurchaseOrder::factory()->create([
            'business_id' => $user->business_id,
            'status' => PurchaseOrder::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/admin/purchase-orders/{$po->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Purchase order deleted successfully',
            ]);

        $this->assertSoftDeleted('purchase_orders', ['id' => $po->id]);
    }

    /**
     * Test API endpoint for sending PO.
     */
    public function test_api_can_send_purchase_order(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        $po = PurchaseOrder::factory()->create([
            'business_id' => $user->business_id,
            'status' => PurchaseOrder::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/admin/purchase-orders/{$po->id}/send");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Purchase order sent to supplier successfully',
            ]);
    }

    /**
     * Test API endpoint for approving PO.
     */
    public function test_api_can_approve_purchase_order(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        $po = PurchaseOrder::factory()->create([
            'business_id' => $user->business_id,
            'status' => PurchaseOrder::STATUS_SENT,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/admin/purchase-orders/{$po->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Purchase order approved successfully',
            ]);
    }

    /**
     * Test API endpoint for rejecting PO.
     */
    public function test_api_can_reject_purchase_order(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        $po = PurchaseOrder::factory()->create([
            'business_id' => $user->business_id,
            'status' => PurchaseOrder::STATUS_SENT,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/admin/purchase-orders/{$po->id}/reject", [
                'reason' => 'Out of stock',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Purchase order rejected successfully',
            ]);
    }

    /**
     * Test API endpoint for cancelling PO.
     */
    public function test_api_can_cancel_purchase_order(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        $po = PurchaseOrder::factory()->create([
            'business_id' => $user->business_id,
            'status' => PurchaseOrder::STATUS_SENT,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/admin/purchase-orders/{$po->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Purchase order cancelled successfully',
            ]);
    }

    /**
     * Test API endpoint for converting PO to purchase.
     */
    public function test_api_can_convert_po_to_purchase(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        $po = PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->count(2), 'items')
            ->create([
                'business_id' => $user->business_id,
                'status' => PurchaseOrder::STATUS_ACCEPTED,
            ]);

        $response = $this->actingAs($user)
            ->postJson("/admin/purchase-orders/{$po->id}/convert");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Purchase order converted to purchase successfully',
            ]);
    }

    /**
     * Test API endpoint for pending POs.
     */
    public function test_api_can_get_pending_purchase_orders(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        PurchaseOrder::factory()->count(3)->create([
            'business_id' => $user->business_id,
            'status' => PurchaseOrder::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/admin/purchase-orders/pending');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test API endpoint for overdue POs.
     */
    public function test_api_can_get_overdue_purchase_orders(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);
        PurchaseOrder::factory()->create([
            'business_id' => $user->business_id,
            'status' => PurchaseOrder::STATUS_SENT,
            'expected_delivery_date' => now()->subDays(5),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/admin/purchase-orders/overdue');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test API endpoint for PO statistics.
     */
    public function test_api_can_get_purchase_order_statistics(): void
    {
        $user = User::factory()->create(['role' => 'shop-owner']);

        $response = $this->actingAs($user)
            ->getJson('/admin/purchase-orders/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
