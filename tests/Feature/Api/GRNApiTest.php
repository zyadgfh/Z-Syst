<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Business;
use App\Models\Warehouse;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GRNApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Business $business;
    protected User $user;
    protected Warehouse $warehouse;
    protected Party $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->warehouse = Warehouse::factory()->create(['business_id' => $this->business->id]);
        $this->supplier = Party::factory()->create(['business_id' => $this->business->id, 'type' => 'Supplier']);
    }

    // -----------------------------------------------------------------------
    // INDEX
    // -----------------------------------------------------------------------

    public function test_index_returns_paginated_grn_list(): void
    {
        GoodsReceivedNote::factory()
            ->count(3)
            ->create(['business_id' => $this->business->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/grn');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'grn_number',
                        'status',
                        'received_date',
                        'purchase_order_id',
                    ],
                ],
            ]);
    }

    public function test_index_filters_by_status(): void
    {
        GoodsReceivedNote::factory()
            ->create(['business_id' => $this->business->id, 'status' => 'draft']);
        GoodsReceivedNote::factory()
            ->create(['business_id' => $this->business->id, 'status' => 'verified']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/grn?status=verified');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_warehouse(): void
    {
        $otherWarehouse = Warehouse::factory()->create(['business_id' => $this->business->id]);

        GoodsReceivedNote::factory()
            ->create(['business_id' => $this->business->id, 'warehouse_id' => $this->warehouse->id]);
        GoodsReceivedNote::factory()
            ->create(['business_id' => $this->business->id, 'warehouse_id' => $otherWarehouse->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/grn?warehouse_id={$this->warehouse->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/grn');

        $response->assertUnauthorized();
    }

    // -----------------------------------------------------------------------
    // STORE
    // -----------------------------------------------------------------------

    public function test_store_creates_grn_with_items(): void
    {
        $purchaseOrder = PurchaseOrder::factory()
            ->create([
                'business_id' => $this->business->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'sent',
            ]);

        $item = PurchaseOrderItem::factory()
            ->create([
                'purchase_order_id' => $purchaseOrder->id,
                'quantity' => 100,
            ]);

        $payload = [
            'purchase_order_id' => $purchaseOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'received_date' => now()->toDateString(),
            'notes' => 'Full shipment received',
            'items' => [
                [
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity_received' => 100,
                    'quantity_accepted' => 98,
                    'quantity_rejected' => 2,
                    'unit_cost' => $item->unit_price ?? 10.00,
                    'batch_number' => $this->faker->bothify('BATCH-####'),
                    'expiry_date' => now()->addYear()->toDateString(),
                    'notes' => '2 items damaged',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/grn', $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'grn_number',
                    'status',
                    'items',
                ],
            ]);

        $this->assertDatabaseHas('goods_received_notes', [
            'business_id' => $this->business->id,
            'purchase_order_id' => $purchaseOrder->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('grn_items', [
            'product_id' => $item->product_id,
            'received_quantity' => 100,
            'accepted_quantity' => 98,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/grn', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'purchase_order_id',
                'warehouse_id',
                'supplier_id',
                'received_date',
                'items',
            ]);
    }

    public function test_store_validates_items_array_not_empty(): void
    {
        $payload = [
            'purchase_order_id' => 1,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'received_date' => now()->toDateString(),
            'items' => [],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/grn', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_store_validates_quantity_received_required(): void
    {
        $purchaseOrder = PurchaseOrder::factory()
            ->create([
                'business_id' => $this->business->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'sent',
            ]);

        $item = PurchaseOrderItem::factory()
            ->create(['purchase_order_id' => $purchaseOrder->id]);

        $payload = [
            'purchase_order_id' => $purchaseOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'received_date' => now()->toDateString(),
            'items' => [
                [
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'unit_cost' => $item->unit_cost,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/grn', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.quantity_received']);
    }

    // -----------------------------------------------------------------------
    // SHOW
    // -----------------------------------------------------------------------

    public function test_show_returns_grn_detail(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->create(['business_id' => $this->business->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/grn/{$grn->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'grn_number',
                    'status',
                    'items',
                ],
            ]);
    }

    public function test_show_returns_404_for_nonexistent_grn(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/grn/99999');

        $response->assertNotFound();
    }

    // -----------------------------------------------------------------------
    // UPDATE
    // -----------------------------------------------------------------------

    public function test_update_modifies_draft_grn(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->create([
                'business_id' => $this->business->id,
                'status' => 'draft',
            ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/grn/{$grn->id}", [
                'notes' => 'Updated notes',
                'received_date' => now()->addDay()->toDateString(),
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('goods_received_notes', [
            'id' => $grn->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_update_rejects_non_draft_grn(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->create([
                'business_id' => $this->business->id,
                'status' => 'verified',
            ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/grn/{$grn->id}", [
                'notes' => 'Should not work',
            ]);

        $response->assertForbidden();
    }

    // -----------------------------------------------------------------------
    // DESTROY
    // -----------------------------------------------------------------------

    public function test_destroy_deletes_draft_grn(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->create([
                'business_id' => $this->business->id,
                'status' => 'draft',
            ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/grn/{$grn->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('goods_received_notes', ['id' => $grn->id]);
    }

    public function test_destroy_rejects_non_draft_grn(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->create([
                'business_id' => $this->business->id,
                'status' => 'verified',
            ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/grn/{$grn->id}");

        $response->assertForbidden();
    }

    // -----------------------------------------------------------------------
    // VERIFY
    // -----------------------------------------------------------------------

    public function test_verify_transitions_draft_to_verified(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->create([
                'business_id' => $this->business->id,
                'status' => 'draft',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/grn/{$grn->id}/verify");

        $response->assertOk();

        $this->assertDatabaseHas('goods_received_notes', [
            'id' => $grn->id,
            'status' => 'verified',
        ]);
    }

    public function test_verify_rejects_already_verified_grn(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->create([
                'business_id' => $this->business->id,
                'status' => 'verified',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/grn/{$grn->id}/verify");

        $response->assertForbidden();
    }

    // -----------------------------------------------------------------------
    // ACCEPT
    // -----------------------------------------------------------------------

    public function test_accept_transitions_verified_to_accepted(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->create([
                'business_id' => $this->business->id,
                'status' => 'verified',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/grn/{$grn->id}/accept");

        $response->assertOk();

        $this->assertDatabaseHas('goods_received_notes', [
            'id' => $grn->id,
            'status' => 'accepted',
        ]);
    }

    public function test_accept_rejects_non_verified_grn(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->create([
                'business_id' => $this->business->id,
                'status' => 'draft',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/grn/{$grn->id}/accept");

        $response->assertForbidden();
    }

    // -----------------------------------------------------------------------
    // PENDING
    // -----------------------------------------------------------------------

    public function test_pending_returns_purchase_orders_awaiting_receipt(): void
    {
        PurchaseOrder::factory()
            ->create([
                'business_id' => $this->business->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'sent',
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/grn/pending');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_pending_excludes_received_orders(): void
    {
        PurchaseOrder::factory()
            ->create([
                'business_id' => $this->business->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'received',
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/grn/pending');

        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }

    // -----------------------------------------------------------------------
    // BUSINESS ISOLATION
    // -----------------------------------------------------------------------

    public function test_user_cannot_access_other_business_grn(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);
        $otherGrn = GoodsReceivedNote::factory()
            ->create(['business_id' => $otherBusiness->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/grn/{$otherGrn->id}");

        $response->assertNotFound();
    }
}
