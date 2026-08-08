<?php

namespace Tests\Feature;

use App\Models\GoodsReceivedNote;
use App\Models\GRNItem;
use App\Models\Product;
use App\Models\Party;
use App\Models\User;
use App\Models\PurchaseOrder;
use App\Services\GRNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GRNTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected GRNService $grnService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grnService = app(GRNService::class);
    }

    /**
     * Test creating a GRN.
     */
    public function test_can_create_grn(): void
    {
        $user = User::factory()->create();
        $supplier = Party::factory()->create(['type' => 'supplier']);
        $product = Product::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'business_id' => $user->business_id,
            'branch_id' => $user->branch_id,
            'received_by' => $user->id,
            'received_date' => now(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'ordered_quantity' => 100,
                    'received_quantity' => 95,
                    'accepted_quantity' => 90,
                    'rejected_quantity' => 5,
                    'purchase_price' => 50.00,
                ],
            ],
        ];

        $grn = $this->grnService->create($data);

        $this->assertInstanceOf(GoodsReceivedNote::class, $grn);
        $this->assertEquals($supplier->id, $grn->supplier_id);
        $this->assertEquals(GoodsReceivedNote::STATUS_PENDING, $grn->status);
        $this->assertNotNull($grn->grn_number);
        $this->assertCount(1, $grn->items);
    }

    /**
     * Test updating a GRN.
     */
    public function test_can_update_grn(): void
    {
        $user = User::factory()->create();
        $grn = GoodsReceivedNote::factory()->create(['business_id' => $user->business_id]);
        $newSupplier = Party::factory()->create(['type' => 'supplier']);

        $data = [
            'supplier_id' => $newSupplier->id,
            'location' => 'Warehouse A',
        ];

        $updatedGrn = $this->grnService->update($grn, $data);

        $this->assertEquals($newSupplier->id, $updatedGrn->supplier_id);
        $this->assertEquals('Warehouse A', $updatedGrn->location);
    }

    /**
     * Test verifying a GRN.
     */
    public function test_can_verify_grn(): void
    {
        $user = User::factory()->create();
        $grn = GoodsReceivedNote::factory()
            ->has(GRNItem::factory()->state([
                'accepted_quantity' => 50,
            ]), 'items')
            ->create(['status' => GoodsReceivedNote::STATUS_PENDING]);

        $verifiedGrn = $this->grnService->verify($grn, $user->id);

        $this->assertEquals(GoodsReceivedNote::STATUS_VERIFIED, $verifiedGrn->status);
        $this->assertEquals($user->id, $verifiedGrn->verified_by);
        $this->assertNotNull($verifiedGrn->verified_at);
    }

    /**
     * Test accepting a GRN.
     */
    public function test_can_accept_grn(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->has(GRNItem::factory()->state([
                'accepted_quantity' => 50,
                'rejected_quantity' => 0,
            ]), 'items')
            ->create(['status' => GoodsReceivedNote::STATUS_VERIFIED]);

        $acceptedGrn = $this->grnService->accept($grn);

        $this->assertEquals(GoodsReceivedNote::STATUS_ACCEPTED, $acceptedGrn->status);
    }

    /**
     * Test partially accepting a GRN.
     */
    public function test_can_partially_accept_grn(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->has(GRNItem::factory()->state([
                'accepted_quantity' => 40,
                'rejected_quantity' => 10,
            ]), 'items')
            ->create(['status' => GoodsReceivedNote::STATUS_VERIFIED]);

        $acceptedGrn = $this->grnService->accept($grn);

        $this->assertEquals(GoodsReceivedNote::STATUS_PARTIALLY_ACCEPTED, $acceptedGrn->status);
    }

    /**
     * Test rejecting a GRN.
     */
    public function test_can_reject_grn(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->has(GRNItem::factory()->state([
                'accepted_quantity' => 0,
                'rejected_quantity' => 50,
            ]), 'items')
            ->create(['status' => GoodsReceivedNote::STATUS_VERIFIED]);

        $rejectedGrn = $this->grnService->reject($grn);

        $this->assertEquals(GoodsReceivedNote::STATUS_REJECTED, $rejectedGrn->status);
    }

    /**
     * Test cannot verify non-pending GRN.
     */
    public function test_cannot_verify_non_pending_grn(): void
    {
        $this->expectException(\Exception::class);

        $user = User::factory()->create();
        $grn = GoodsReceivedNote::factory()->create(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->grnService->verify($grn, $user->id);
    }

    /**
     * Test cannot accept non-verified GRN.
     */
    public function test_cannot_accept_non_verified_grn(): void
    {
        $this->expectException(\Exception::class);

        $grn = GoodsReceivedNote::factory()->create(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->grnService->accept($grn);
    }

    /**
     * Test cannot reject non-verified GRN.
     */
    public function test_cannot_reject_non_verified_grn(): void
    {
        $this->expectException(\Exception::class);

        $grn = GoodsReceivedNote::factory()->create(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->grnService->reject($grn);
    }

    /**
     * Test cannot delete verified GRN.
     */
    public function test_cannot_delete_verified_grn(): void
    {
        $this->expectException(\Exception::class);

        $grn = GoodsReceivedNote::factory()->create(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->grnService->delete($grn);
    }

    /**
     * Test GRN number generation.
     */
    public function test_grn_number_is_generated(): void
    {
        $grn = GoodsReceivedNote::factory()->create();

        $this->assertNotNull($grn->grn_number);
        $this->assertMatchesRegularExpression('/^GRN-\d{8}-\d{6}$/', $grn->grn_number);
    }

    /**
     * Test GRN total received quantity.
     */
    public function test_grn_total_received_quantity(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->has(GRNItem::factory()->state(['received_quantity' => 50]), 'items')
            ->has(GRNItem::factory()->state(['received_quantity' => 30]), 'items')
            ->create();

        $this->assertEquals(80, $grn->total_received_quantity);
    }

    /**
     * Test GRN total accepted quantity.
     */
    public function test_grn_total_accepted_quantity(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->has(GRNItem::factory()->state(['accepted_quantity' => 45]), 'items')
            ->has(GRNItem::factory()->state(['accepted_quantity' => 25]), 'items')
            ->create();

        $this->assertEquals(70, $grn->total_accepted_quantity);
    }

    /**
     * Test GRN total value.
     */
    public function test_grn_total_value(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->has(GRNItem::factory()->state([
                'accepted_quantity' => 10,
                'purchase_price' => 50.00,
            ]), 'items')
            ->has(GRNItem::factory()->state([
                'accepted_quantity' => 5,
                'purchase_price' => 100.00,
            ]), 'items')
            ->create();

        $expectedValue = (10 * 50.00) + (5 * 100.00);
        $this->assertEquals($expectedValue, $grn->total_value);
    }

    /**
     * Test GRN completion percentage.
     */
    public function test_grn_completion_percentage(): void
    {
        $grn = GoodsReceivedNote::factory()
            ->has(GRNItem::factory()->state([
                'ordered_quantity' => 100,
                'received_quantity' => 50,
            ]), 'items')
            ->create();

        $this->assertEquals(50, $grn->completion_percentage);
    }

    /**
     * Test GRN business scope.
     */
    public function test_grn_business_scope(): void
    {
        $business1 = User::factory()->create()->business_id;
        $business2 = User::factory()->create()->business_id;

        $grn1 = GoodsReceivedNote::factory()->create(['business_id' => $business1]);
        $grn2 = GoodsReceivedNote::factory()->create(['business_id' => $business2]);

        $business1Grns = GoodsReceivedNote::forBusiness($business1)->get();

        $this->assertCount(1, $business1Grns);
        $this->assertEquals($grn1->id, $business1Grns->first()->id);
    }

    /**
     * Test GRN supplier scope.
     */
    public function test_grn_supplier_scope(): void
    {
        $supplier1 = Party::factory()->create(['type' => 'supplier']);
        $supplier2 = Party::factory()->create(['type' => 'supplier']);

        $grn1 = GoodsReceivedNote::factory()->create(['supplier_id' => $supplier1->id]);
        $grn2 = GoodsReceivedNote::factory()->create(['supplier_id' => $supplier2->id]);

        $supplier1Grns = GoodsReceivedNote::forSupplier($supplier1->id)->get();

        $this->assertCount(1, $supplier1Grns);
        $this->assertEquals($grn1->id, $supplier1Grns->first()->id);
    }

    /**
     * Test GRN status scope.
     */
    public function test_grn_status_scope(): void
    {
        $grn1 = GoodsReceivedNote::factory()->create(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $grn2 = GoodsReceivedNote::factory()->create(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $grn3 = GoodsReceivedNote::factory()->create(['status' => GoodsReceivedNote::STATUS_PENDING]);

        $pendingGrns = GoodsReceivedNote::byStatus(GoodsReceivedNote::STATUS_PENDING)->get();

        $this->assertCount(2, $pendingGrns);
    }

    /**
     * Test GRN statistics.
     */
    public function test_get_grn_statistics(): void
    {
        $businessId = User::factory()->create()->business_id;

        GoodsReceivedNote::factory()->count(5)->create([
            'business_id' => $businessId,
            'status' => GoodsReceivedNote::STATUS_PENDING,
        ]);
        GoodsReceivedNote::factory()->count(3)->create([
            'business_id' => $businessId,
            'status' => GoodsReceivedNote::STATUS_VERIFIED,
        ]);
        GoodsReceivedNote::factory()->count(2)->create([
            'business_id' => $businessId,
            'status' => GoodsReceivedNote::STATUS_ACCEPTED,
        ]);

        $stats = $this->grnService->getStatistics($businessId);

        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(5, $stats['pending']);
        $this->assertEquals(3, $stats['verified']);
        $this->assertEquals(2, $stats['accepted']);
    }

    /**
     * Test API endpoint for creating GRN.
     */
    public function test_api_can_create_grn(): void
    {
        $user = User::factory()->create();
        $supplier = Party::factory()->create(['type' => 'supplier']);
        $product = Product::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'received_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => $product->id,
                    'ordered_quantity' => 100,
                    'received_quantity' => 95,
                    'purchase_price' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/grn', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'GRN created successfully',
            ]);
    }

    /**
     * Test API endpoint for listing GRNs.
     */
    public function test_api_can_list_grns(): void
    {
        $user = User::factory()->create();
        GoodsReceivedNote::factory()->count(5)->create(['business_id' => $user->business_id]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/grn');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test API endpoint for showing GRN.
     */
    public function test_api_can_show_grn(): void
    {
        $user = User::factory()->create();
        $grn = GoodsReceivedNote::factory()->create(['business_id' => $user->business_id]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v1/grn/{$grn->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test API endpoint for updating GRN.
     */
    public function test_api_can_update_grn(): void
    {
        $user = User::factory()->create();
        $grn = GoodsReceivedNote::factory()->create(['business_id' => $user->business_id]);
        $newSupplier = Party::factory()->create(['type' => 'supplier']);

        $data = [
            'supplier_id' => $newSupplier->id,
            'location' => 'Warehouse B',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson("/api/v1/grn/{$grn->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'GRN updated successfully',
            ]);
    }

    /**
     * Test API endpoint for deleting GRN.
     */
    public function test_api_can_delete_grn(): void
    {
        $user = User::factory()->create();
        $grn = GoodsReceivedNote::factory()->create([
            'business_id' => $user->business_id,
            'status' => GoodsReceivedNote::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/v1/grn/{$grn->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'GRN deleted successfully',
            ]);

        $this->assertSoftDeleted('goods_received_notes', ['id' => $grn->id]);
    }

    /**
     * Test API endpoint for verifying GRN.
     */
    public function test_api_can_verify_grn(): void
    {
        $user = User::factory()->create();
        $grn = GoodsReceivedNote::factory()->create([
            'business_id' => $user->business_id,
            'status' => GoodsReceivedNote::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/grn/{$grn->id}/verify");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'GRN verified successfully',
            ]);
    }

    /**
     * Test API endpoint for accepting GRN.
     */
    public function test_api_can_accept_grn(): void
    {
        $user = User::factory()->create();
        $grn = GoodsReceivedNote::factory()->create([
            'business_id' => $user->business_id,
            'status' => GoodsReceivedNote::STATUS_VERIFIED,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/grn/{$grn->id}/accept");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'GRN accepted successfully',
            ]);
    }

    /**
     * Test API endpoint for rejecting GRN.
     */
    public function test_api_can_reject_grn(): void
    {
        $user = User::factory()->create();
        $grn = GoodsReceivedNote::factory()->create([
            'business_id' => $user->business_id,
            'status' => GoodsReceivedNote::STATUS_VERIFIED,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/grn/{$grn->id}/reject");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'GRN rejected successfully',
            ]);
    }

    /**
     * Test API endpoint for pending GRNs.
     */
    public function test_api_can_get_pending_grns(): void
    {
        $user = User::factory()->create();
        GoodsReceivedNote::factory()->count(3)->create([
            'business_id' => $user->business_id,
            'status' => GoodsReceivedNote::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/grn/pending');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test API endpoint for GRN statistics.
     */
    public function test_api_can_get_grn_statistics(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/grn/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
