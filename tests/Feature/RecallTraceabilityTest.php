<?php

namespace Tests\Feature;

use App\Models\BatchLot;
use App\Models\Business;
use App\Models\Product;
use App\Models\RecallEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('recall')]
#[Group('traceability')]
#[Group('new-features')]
class RecallTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $user;
    private Product $product;
    private BatchLot $batchLot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);
        $this->batchLot = BatchLot::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-TEST-001',
            'quantity' => 100,
            'status' => 'active',
        ]);
    }

    // ── Authentication ─────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_recalls(): void
    {
        $response = $this->getJson('/api/v1/traceability/recalls');
        $response->assertStatus(401);
    }

    // ── List Recalls ───────────────────────────────────────────────

    public function test_can_list_recalls(): void
    {
        RecallEvent::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/traceability/recalls');

        $response->assertOk()
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_can_filter_recalls_by_status(): void
    {
        RecallEvent::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'active',
        ]);
        RecallEvent::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'resolved',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/traceability/recalls?status=active');

        $response->assertOk();
        $data = $response->json('data');
        // Should only contain active recalls
        foreach ($data as $recall) {
            $this->assertEquals('active', $recall['status']);
        }
    }

    // ── Initiate Recall ────────────────────────────────────────────

    public function test_can_initiate_recall(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/traceability/recalls', [
                'business_id' => $this->business->id,
                'product_id' => $this->product->id,
                'batch_lot_number' => 'BATCH-TEST-001',
                'reason' => 'Contamination risk detected',
                'description' => 'Microbial contamination found in batch',
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('recall_events', [
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'reason' => 'Contamination risk detected',
            'status' => 'active',
        ]);
    }

    public function test_initiate_recall_requires_reason(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/traceability/recalls', [
                'business_id' => $this->business->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_initiate_recall_links_affected_batches(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/traceability/recalls', [
                'business_id' => $this->business->id,
                'product_id' => $this->product->id,
                'batch_lot_number' => 'BATCH-TEST-001',
                'reason' => 'Quality concern',
            ]);

        $response->assertCreated();

        // The batch should be linked to the recall
        $recall = RecallEvent::where('business_id', $this->business->id)->first();
        $this->assertNotNull($recall);

        $recall->load('affectedBatches');
        $this->assertCount(1, $recall->affectedBatches);

        // The batch should be marked as recalled
        $this->batchLot->refresh();
        $this->assertNotNull($this->batchLot->recall_date);
    }

    // ── Recall Summary ─────────────────────────────────────────────

    public function test_can_get_recall_summary(): void
    {
        $recall = RecallEvent::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_lot_number' => 'BATCH-TEST-001',
            'reason' => 'Quality concern',
        ]);

        $recall->affectedBatches()->attach($this->batchLot->id, [
            'quarantine_status' => 'pending',
            'quantity_affected' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/traceability/recalls/{$recall->id}/summary");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'recall',
                    'affected_batches',
                    'summary',
                ],
            ]);

        $summary = $response->json('data.summary');
        $this->assertEquals(1, $summary['total_batches']);
        $this->assertEquals(100, $summary['total_quantity_affected']);
        $this->assertEquals(1, $summary['pending_count']);
    }

    // ── Quarantine Batch ───────────────────────────────────────────

    public function test_can_quarantine_affected_batch(): void
    {
        $recall = RecallEvent::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'status' => 'active',
        ]);

        $recall->affectedBatches()->attach($this->batchLot->id, [
            'quarantine_status' => 'pending',
            'quantity_affected' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/traceability/recalls/{$recall->id}/quarantine", [
                'batch_lot_id' => $this->batchLot->id,
                'notes' => 'Quarantined due to contamination',
            ]);

        $response->assertOk();

        // Verify batch is quarantined
        $this->batchLot->refresh();
        $this->assertEquals('quarantined', $this->batchLot->status);

        // Verify pivot updated
        $pivot = $recall->fresh()->affectedBatches()->where('batch_lots.id', $this->batchLot->id)->first()->pivot;
        $this->assertEquals('quarantined', $pivot->quarantine_status);
        $this->assertNotNull($pivot->quarantined_at);
    }

    public function test_quarantine_requires_batch_lot_id(): void
    {
        $recall = RecallEvent::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/traceability/recalls/{$recall->id}/quarantine", []);

        $response->assertStatus(422);
    }

    public function test_quarantine_creates_traceability_log(): void
    {
        $recall = RecallEvent::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'status' => 'active',
        ]);

        $recall->affectedBatches()->attach($this->batchLot->id, [
            'quarantine_status' => 'pending',
            'quantity_affected' => 100,
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/traceability/recalls/{$recall->id}/quarantine", [
                'batch_lot_id' => $this->batchLot->id,
            ]);

        $this->assertDatabaseHas('traceability_logs', [
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'type' => 'recall',
        ]);
    }

    // ── Release Batch ──────────────────────────────────────────────

    public function test_can_release_affected_batch(): void
    {
        $recall = RecallEvent::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'active',
        ]);

        $recall->affectedBatches()->attach($this->batchLot->id, [
            'quarantine_status' => 'quarantined',
            'quantity_affected' => 100,
        ]);

        $this->batchLot->update(['status' => 'quarantined']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/traceability/recalls/{$recall->id}/release", [
                'batch_lot_id' => $this->batchLot->id,
            ]);

        $response->assertOk();

        // Verify batch is released
        $this->batchLot->refresh();
        $this->assertEquals('active', $this->batchLot->status);

        // Verify pivot updated
        $pivot = $recall->fresh()->affectedBatches()->where('batch_lots.id', $this->batchLot->id)->first()->pivot;
        $this->assertEquals('released', $pivot->quarantine_status);
        $this->assertNotNull($pivot->resolved_at);
    }

    // ── Dispose Batch ──────────────────────────────────────────────

    public function test_can_dispose_affected_batch(): void
    {
        $recall = RecallEvent::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'status' => 'active',
        ]);

        $recall->affectedBatches()->attach($this->batchLot->id, [
            'quarantine_status' => 'quarantined',
            'quantity_affected' => 100,
        ]);

        $this->batchLot->update(['status' => 'quarantined']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/traceability/recalls/{$recall->id}/dispose", [
                'batch_lot_id' => $this->batchLot->id,
                'notes' => 'Disposed per regulatory requirement',
            ]);

        $response->assertOk();

        // Verify batch is disposed with zero quantity
        $this->batchLot->refresh();
        $this->assertEquals('disposed', $this->batchLot->status);
        $this->assertEquals(0, $this->batchLot->quantity);

        // Verify pivot updated
        $pivot = $recall->fresh()->affectedBatches()->where('batch_lots.id', $this->batchLot->id)->first()->pivot;
        $this->assertEquals('disposed', $pivot->quarantine_status);
        $this->assertNotNull($pivot->resolved_at);
    }

    public function test_dispose_creates_traceability_log(): void
    {
        $recall = RecallEvent::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'status' => 'active',
        ]);

        $recall->affectedBatches()->attach($this->batchLot->id, [
            'quarantine_status' => 'quarantined',
            'quantity_affected' => 100,
        ]);

        $this->batchLot->update(['status' => 'quarantined']);

        $this->actingAs($this->user)
            ->postJson("/api/v1/traceability/recalls/{$recall->id}/dispose", [
                'batch_lot_id' => $this->batchLot->id,
                'notes' => 'Regulatory disposal',
            ]);

        $this->assertDatabaseHas('traceability_logs', [
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'type' => 'recall',
            'quantity' => 0,
        ]);
    }

    // ── Resolve Recall ─────────────────────────────────────────────

    public function test_can_resolve_recall(): void
    {
        $recall = RecallEvent::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/traceability/recalls/{$recall->id}/resolve");

        $response->assertOk();

        $recall->refresh();
        $this->assertEquals('resolved', $recall->status);
        $this->assertNotNull($recall->resolved_at);
    }

    // ── Detect Affected Batches ────────────────────────────────────

    public function test_can_detect_affected_batches(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/traceability/detect-affected?product_id=' . $this->product->id);

        $response->assertOk()
            ->assertJsonStructure(['message', 'data', 'total']);

        $this->assertGreaterThanOrEqual(1, $response->json('total'));
    }

    public function test_detect_affected_batches_filters_by_batch_number(): void
    {
        // Create a second batch that should NOT match
        BatchLot::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-OTHER-999',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/traceability/detect-affected?batch_lot_number=BATCH-TEST-001');

        $response->assertOk();
        $data = $response->json('data');

        // Should only contain our specific batch
        foreach ($data as $batch) {
            $this->assertEquals('BATCH-TEST-001', $batch['batch_number']);
        }
    }

    // ── Tenant Isolation ───────────────────────────────────────────

    public function test_user_cannot_access_other_business_recalls(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

        $recall = RecallEvent::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/traceability/recalls/{$recall->id}/summary");

        $response->assertStatus(403);
    }

    // ── Batch Lot Management ───────────────────────────────────────

    public function test_can_list_batch_lots(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/traceability/batch-lots');

        $response->assertOk()
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_can_get_expiring_batches(): void
    {
        // Create a batch expiring within 30 days
        BatchLot::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'expiry_date' => now()->addDays(15),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/traceability/expiring-batches?days=30');

        $response->assertOk()
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_can_get_expired_batches(): void
    {
        // Create an expired batch
        BatchLot::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'expiry_date' => now()->subDays(10),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/traceability/expired-batches');

        $response->assertOk()
            ->assertJsonStructure(['message', 'data']);
    }
}
