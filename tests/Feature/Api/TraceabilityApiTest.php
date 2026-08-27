<?php

namespace Tests\Feature\Api;

use App\Models\BatchLot;
use App\Models\Business;
use App\Models\Product;
use App\Models\RecallEvent;
use App\Models\TraceabilityLog;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraceabilityApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);
    }

    public function test_can_list_batch_lots(): void
    {
        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-001',
            'lot_number' => 'LOT-001',
            'expiry_date' => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/traceability/batch-lots');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'batch_number', 'lot_number', 'expiry_date'],
                ],
            ]);
    }

    public function test_can_list_batch_lots_filtered_by_product(): void
    {
        $otherProduct = Product::factory()->create(['business_id' => $this->business->id]);

        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'PROD1-BATCH',
        ]);

        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $otherProduct->id,
            'batch_number' => 'PROD2-BATCH',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/traceability/batch-lots?product_id={$this->product->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_view_expiring_batches(): void
    {
        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'EXPIRING-001',
            'expiry_date' => now()->addDays(15),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/traceability/expiring-batches');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_view_expired_batches(): void
    {
        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'EXPIRED-001',
            'expiry_date' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/traceability/expired-batches');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_list_recalls(): void
    {
        RecallEvent::create([
            'business_id' => $this->business->id,
            'batch_lot_number' => 'LOT-001',
            'reason' => 'Contamination',
            'initiated_at' => now()->toDateString(),
            'status' => 'active',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/traceability/recalls');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'batch_lot_number', 'reason', 'status'],
                ],
            ]);
    }

    public function test_can_initiate_recall(): void
    {
        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-001',
            'lot_number' => 'LOT-001',
            'expiry_date' => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/traceability/recalls', [
                'business_id' => $this->business->id,
                'product_id' => $this->product->id,
                'batch_lot_number' => 'LOT-001',
                'reason' => 'Contamination detected',
                'initiated_at' => now()->toDateString(),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.reason', 'Contamination detected')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('recall_events', [
            'business_id' => $this->business->id,
            'batch_lot_number' => 'LOT-001',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('batch_lots', [
            'business_id' => $this->business->id,
            'lot_number' => 'LOT-001',
        ]);
        $this->assertNotNull(BatchLot::where('lot_number', 'LOT-001')->first()->recall_date);
    }

    public function test_recall_validation_requires_batch_and_reason(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/traceability/recalls', [
                'initiated_at' => now()->toDateString(),
            ]);

        $response->assertStatus(422);
    }

    public function test_can_resolve_recall(): void
    {
        $recall = RecallEvent::create([
            'business_id' => $this->business->id,
            'batch_lot_number' => 'LOT-001',
            'reason' => 'Contamination',
            'initiated_at' => now()->toDateString(),
            'status' => 'active',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/traceability/recalls/{$recall->id}/resolve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'resolved');
    }

    public function test_can_view_traceability_logs(): void
    {
        $wh1 = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'WH1',
            'code' => 'WH-TRACE-1',
        ]);

        $wh2 = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'WH2',
            'code' => 'WH-TRACE-2',
        ]);

        TraceabilityLog::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_lot_number' => 'TRACE-BATCH-001',
            'from_warehouse_id' => $wh1->id,
            'to_warehouse_id' => $wh2->id,
            'type' => 'transfer',
            'quantity' => 50,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/traceability/traceability?product_id={$this->product->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'product_id', 'batch_lot_number', 'type', 'quantity'],
                ],
            ]);
    }
}
