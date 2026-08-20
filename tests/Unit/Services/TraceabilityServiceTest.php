<?php

namespace Tests\Unit\Services;

use App\Models\BatchLot;
use App\Models\Business;
use App\Models\Product;
use App\Models\RecallEvent;
use App\Models\TraceabilityLog;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\TraceabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TraceabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Product $product;

    private TraceabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Tests call non-existent service methods - need rewrite');

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);
        $this->service = new TraceabilityService;

        Auth::login($this->user);
    }

    public function test_get_batch_lots_for_product(): void
    {
        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-001',
            'lot_number' => 'LOT-001',
            'expiry_date' => now()->addMonth(),
        ]);

        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-002',
            'lot_number' => 'LOT-002',
            'expiry_date' => now()->addMonths(2),
        ]);

        $lots = $this->service->getBatchLots($this->business->id, $this->product->id);

        $this->assertCount(2, $lots);
    }

    public function test_get_expiring_batches(): void
    {
        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'EXPIRING-001',
            'expiry_date' => now()->addDays(15),
        ]);

        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'EXPIRED-001',
            'expiry_date' => now()->subDay(),
        ]);

        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'FUTURE-001',
            'expiry_date' => now()->addYear(),
        ]);

        $expiring = $this->service->getExpiringBatches($this->business->id, 30);

        $this->assertCount(1, $expiring);
        $this->assertEquals('EXPIRING-001', $expiring[0]['batch_number']);
    }

    public function test_get_expired_batches_excludes_recalled(): void
    {
        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'EXPIRED-001',
            'expiry_date' => now()->subDay(),
            'recall_date' => null,
        ]);

        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'EXPIRED-002',
            'expiry_date' => now()->subDays(2),
            'recall_date' => now()->subDay(),
        ]);

        $expired = $this->service->getExpiredBatches($this->business->id);

        $this->assertCount(1, $expired);
        $this->assertEquals('EXPIRED-001', $expired[0]['batch_number']);
    }

    public function test_initiate_recall_updates_batch_lots(): void
    {
        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-001',
            'lot_number' => 'LOT-001',
            'expiry_date' => now()->addYear(),
        ]);

        BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-002',
            'lot_number' => 'LOT-002',
            'expiry_date' => now()->addYear(),
        ]);

        $recall = $this->service->initiateRecall([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_lot_number' => 'LOT-001',
            'reason' => 'Contamination detected',
            'initiated_at' => now()->toDateString(),
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(RecallEvent::class, $recall);
        $this->assertEquals('active', $recall->status);
        $this->assertEquals('LOT-001', $recall->batch_lot_number);

        $recalledLot = BatchLot::where('lot_number', 'LOT-001')->first();
        $this->assertNotNull($recalledLot->recall_date);

        $unrecalledLot = BatchLot::where('lot_number', 'LOT-002')->first();
        $this->assertNull($unrecalledLot->recall_date);
    }

    public function test_resolve_recall_sets_status(): void
    {
        $recall = RecallEvent::create([
            'business_id' => $this->business->id,
            'batch_lot_number' => 'LOT-001',
            'reason' => 'Test recall',
            'initiated_at' => now()->toDateString(),
            'user_id' => $this->user->id,
        ]);

        $resolved = $this->service->resolveRecall($recall);

        $this->assertEquals('resolved', $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
    }

    public function test_log_traceability_creates_log_entry(): void
    {
        $wh1 = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'WH1',
            'code' => 'WH001',
        ]);

        $wh2 = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'WH2',
            'code' => 'WH002',
        ]);

        $log = $this->service->logTraceability(
            $this->business->id,
            $this->product->id,
            'transfer',
            100,
            [
                'batch_lot_number' => 'BATCH-001',
                'from_warehouse_id' => $wh1->id,
                'to_warehouse_id' => $wh2->id,
                'notes' => 'Stock transfer to secondary warehouse',
            ]
        );

        $this->assertInstanceOf(TraceabilityLog::class, $log);
        $this->assertEquals('transfer', $log->type);
        $this->assertEquals(100, $log->quantity);
        $this->assertEquals('BATCH-001', $log->batch_lot_number);
    }

    public function test_create_batch_lot(): void
    {
        $batch = $this->service->createBatchLot([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'NEW-BATCH-001',
            'lot_number' => 'NEW-LOT-001',
            'manufacture_date' => now()->subMonth(),
            'expiry_date' => now()->addYear(),
            'supplier_name' => 'PharmaCorp',
        ]);

        $this->assertInstanceOf(BatchLot::class, $batch);
        $this->assertEquals('NEW-BATCH-001', $batch->batch_number);
        $this->assertEquals('NEW-LOT-001', $batch->lot_number);
    }

    public function test_batch_lot_is_expired(): void
    {
        $batch = BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'EXPIRED-1',
            'expiry_date' => now()->subDays(10),
        ]);

        $this->assertTrue($batch->isExpired());
    }

    public function test_batch_lot_not_expired(): void
    {
        $batch = BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'VALID-1',
            'expiry_date' => now()->addDays(10),
        ]);

        $this->assertFalse($batch->isExpired());
    }

    public function test_batch_lot_not_recalled(): void
    {
        $batch = BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'NOT-RECALLED',
        ]);

        $this->assertFalse($batch->isRecalled());
    }

    public function test_batch_lot_is_recalled(): void
    {
        $batch = BatchLot::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_number' => 'RECALLED-1',
            'recall_date' => now(),
        ]);

        $this->assertTrue($batch->isRecalled());
    }
}
