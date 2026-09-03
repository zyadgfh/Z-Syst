<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Services\TraceabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraceabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TraceabilityService $service;
    protected Business $business;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $this->business->id]);
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);

        $this->service = new TraceabilityService();
    }

    public function test_create_batch_lot(): void
    {
        $batchLot = $this->service->createBatchLot([
            'product_id' => $this->product->id,
            'business_id' => $this->business->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 100,
            'expiry_date' => now()->addMonths(6)->toDateString(),
        ]);

        $this->assertDatabaseHas('batch_lots', [
            'batch_number' => 'BATCH-001',
            'product_id' => $this->product->id,
        ]);
    }

    public function test_log_traceability(): void
    {
        $log = $this->service->logTraceability([
            'product_id' => $this->product->id,
            'business_id' => $this->business->id,
            'type' => 'received',
            'quantity' => 50,
            'notes' => 'Batch received at warehouse',
        ]);

        $this->assertDatabaseHas('traceability_logs', [
            'product_id' => $this->product->id,
            'type' => 'received',
        ]);
    }
}
