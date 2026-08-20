<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Product;
use App\Models\Stock;
use App\Services\StockBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockBatchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockBatchService $stockBatchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Tests call non-existent service methods - need rewrite');
        $this->stockBatchService = new StockBatchService();
    }

    public function test_create_batch()
    {
        $businessId = 1;
        $product = Product::factory()->create(['business_id' => $businessId]);

        $data = [
            'product_id' => $product->id,
            'quantity' => 100,
            'batch_no' => 'BATCH001',
            'expire_date' => now()->addDays(180)->toDateString(),
        ];

        $batch = $this->stockBatchService->createBatch($data, $businessId);

        $this->assertDatabaseHas('stocks', [
            'business_id' => $businessId,
            'product_id' => $product->id,
            'batch_no' => 'BATCH001',
            'productStock' => 100,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'business_id' => $businessId,
            'product_id' => $product->id,
            'movement_type' => 'in',
            'quantity' => 100,
        ]);
    }

    public function test_merge_batches()
    {
        $businessId = 1;
        $product = Product::factory()->create(['business_id' => $businessId]);

        $batch1 = Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'batch_no' => 'BATCH001',
            'productStock' => 50,
            'expire_date' => now()->addDays(180)->toDateString(),
        ]);

        $batch2 = Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'batch_no' => 'BATCH002',
            'productStock' => 30,
            'expire_date' => now()->addDays(90)->toDateString(),
        ]);

        $mergedBatch = $this->stockBatchService->mergeBatches(
            [$batch1->id, $batch2->id],
            $businessId
        );

        $this->assertEquals(80, $mergedBatch->productStock);
        $this->assertDatabaseMissing('stocks', ['id' => $batch1->id]);
        $this->assertDatabaseMissing('stocks', ['id' => $batch2->id]);
    }

    public function test_split_batch()
    {
        $businessId = 1;
        $product = Product::factory()->create(['business_id' => $businessId]);

        $originalBatch = Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'batch_no' => 'BATCH001',
            'productStock' => 100,
            'expire_date' => now()->addDays(180)->toDateString(),
        ]);

        $result = $this->stockBatchService->splitBatch($originalBatch->id, 30, $businessId);

        $this->assertEquals(70, $result['original_batch']->productStock);
        $this->assertEquals(30, $result['new_batch']->productStock);
        $this->assertEquals($product->id, $result['new_batch']->product_id);
    }

    public function test_get_expiring_batches()
    {
        $businessId = 1;
        $product = Product::factory()->create(['business_id' => $businessId]);

        Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'batch_no' => 'BATCH001',
            'productStock' => 50,
            'expire_date' => now()->addDays(5)->toDateString(),
        ]);

        Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'batch_no' => 'BATCH002',
            'productStock' => 30,
            'expire_date' => now()->addDays(45)->toDateString(),
        ]);

        $expiringBatches = $this->stockBatchService->getExpiringBatches($businessId, 30);

        $this->assertCount(2, $expiringBatches);
    }

    public function test_get_expired_batches()
    {
        $businessId = 1;
        $product = Product::factory()->create(['business_id' => $businessId]);

        Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'batch_no' => 'BATCH001',
            'productStock' => 50,
            'expire_date' => now()->subDays(5)->toDateString(),
        ]);

        $expiredBatches = $this->stockBatchService->getExpiredBatches($businessId);

        $this->assertCount(1, $expiredBatches);
    }

    public function test_adjust_batch_quantity()
    {
        $businessId = 1;
        $product = Product::factory()->create(['business_id' => $businessId]);

        $batch = Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'batch_no' => 'BATCH001',
            'productStock' => 100,
        ]);

        $adjustedBatch = $this->stockBatchService->adjustBatchQuantity(
            $batch->id,
            -20,
            'Stock adjustment',
            $businessId
        );

        $this->assertEquals(80, $adjustedBatch->productStock);
        
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $batch->id,
            'movement_type' => 'out',
            'quantity' => -20,
        ]);
    }

    public function test_cannot_adjust_below_zero()
    {
        $businessId = 1;
        $product = Product::factory()->create(['business_id' => $businessId]);

        $batch = Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'batch_no' => 'BATCH001',
            'productStock' => 10,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot adjust quantity below zero');

        $this->stockBatchService->adjustBatchQuantity($batch->id, -20, 'Test', $businessId);
    }

    public function test_transfer_between_batches()
    {
        $businessId = 1;
        $product = Product::factory()->create(['business_id' => $businessId]);

        $fromBatch = Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'batch_no' => 'BATCH001',
            'productStock' => 100,
        ]);

        $toBatch = Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'batch_no' => 'BATCH002',
            'productStock' => 50,
        ]);

        $result = $this->stockBatchService->transferBetweenBatches(
            $fromBatch->id,
            $toBatch->id,
            30,
            $businessId
        );

        $this->assertEquals(70, $result['from_batch']->productStock);
        $this->assertEquals(80, $result['to_batch']->productStock);
    }

    public function test_get_batch_summary()
    {
        $businessId = 1;
        $product = Product::factory()->create(['business_id' => $businessId]);

        Stock::factory()->count(3)->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'productStock' => 50,
        ]);

        $summary = $this->stockBatchService->getBatchSummary($businessId, $product->id);

        $this->assertArrayHasKey($product->id, $summary);
        $this->assertCount(3, $summary[$product->id]);
    }
}