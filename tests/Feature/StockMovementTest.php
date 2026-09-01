<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\FEFODispensingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Business $business;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
    }

    #[Test]
    public function it_can_create_stock_in_movement()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 50,
        ]);

        $movement = StockMovement::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'user_id' => $this->user->id,
            'movement_type' => 'in',
            'quantity' => 20,
            'reference_type' => 'App\Models\Purchase',
            'reference_id' => 1,
            'notes' => 'Initial stock addition',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'id' => $movement->id,
            'movement_type' => 'in',
            'quantity' => 20,
        ]);
    }

    #[Test]
    public function it_can_create_stock_out_movement()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 50,
        ]);

        $movement = StockMovement::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'user_id' => $this->user->id,
            'movement_type' => 'out',
            'quantity' => 10,
            'reference_type' => 'App\Models\Sale',
            'reference_id' => 1,
            'notes' => 'Sale stock deduction',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'id' => $movement->id,
            'movement_type' => 'out',
            'quantity' => 10,
        ]);
    }

    #[Test]
    public function it_can_create_stock_transfer_movement()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 50,
        ]);

        $movement = StockMovement::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'user_id' => $this->user->id,
            'movement_type' => 'transfer',
            'quantity' => 15,
            'reference_type' => 'App\Models\StockTransfer',
            'reference_id' => 1,
            'notes' => 'Transfer to warehouse',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'id' => $movement->id,
            'movement_type' => 'transfer',
            'quantity' => 15,
        ]);
    }

    #[Test]
    public function it_can_create_stock_adjustment_movement()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 50,
        ]);

        $movement = StockMovement::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'user_id' => $this->user->id,
            'movement_type' => 'adjustment',
            'quantity' => 5,
            'reference_type' => 'App\Models\StockAdjustment',
            'reference_id' => 1,
            'notes' => 'Physical count adjustment',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'id' => $movement->id,
            'movement_type' => 'adjustment',
            'quantity' => 5,
        ]);
    }

    #[Test]
    public function fefo_service_dispenses_from_earliest_expiring_batch()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        // Create multiple batches with different expiry dates
        $stock1 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 30,
            'batch_no' => 'BATCH-001',
            'expire_date' => now()->addDays(30),
        ]);

        $stock2 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 20,
            'batch_no' => 'BATCH-002',
            'expire_date' => now()->addDays(10), // Expires earlier
        ]);

        $stock3 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 15,
            'batch_no' => 'BATCH-003',
            'expire_date' => now()->addDays(60),
        ]);

        $fefoService = app(FEFODispensingService::class);

        // Request 25 units - should dispense from BATCH-002 (earliest expiry) first
        $dispensingPlan = $fefoService->dispense($product->id, 25, $this->business->id);

        $this->assertCount(2, $dispensingPlan);
        $this->assertEquals($stock2->id, $dispensingPlan[0]['stock_id']); // BATCH-002 first
        $this->assertEquals(20, $dispensingPlan[0]['quantity']); // All 20 from BATCH-002
        $this->assertEquals($stock1->id, $dispensingPlan[1]['stock_id']); // Then BATCH-001
        $this->assertEquals(5, $dispensingPlan[1]['quantity']); // 5 from BATCH-001
    }

    #[Test]
    public function fefo_service_throws_exception_when_insufficient_stock()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 10,
        ]);

        $fefoService = app(FEFODispensingService::class);

        $this->expectException(\App\Exceptions\InsufficientStockException::class);

        $fefoService->dispense($product->id, 20, $this->business->id);
    }

    #[Test]
    public function fefo_service_can_dispense_from_specific_batch()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $stock1 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 30,
            'batch_no' => 'BATCH-001',
        ]);

        $stock2 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 20,
            'batch_no' => 'BATCH-002',
        ]);

        $fefoService = app(FEFODispensingService::class);

        // Manually select BATCH-002
        $dispensingPlan = $fefoService->dispenseFromBatch($stock2->id, 15, $this->business->id);

        $this->assertCount(1, $dispensingPlan);
        $this->assertEquals($stock2->id, $dispensingPlan[0]['stock_id']);
        $this->assertEquals(15, $dispensingPlan[0]['quantity']);
    }

    #[Test]
    public function it_can_get_expiring_batches()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $stock1 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 10,
            'expire_date' => now()->addDays(15), // Expiring soon
        ]);

        $stock2 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 20,
            'expire_date' => now()->addDays(60), // Not expiring soon
        ]);

        $stock3 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 5,
            'expire_date' => now()->subDays(5), // Already expired
        ]);

        $fefoService = app(FEFODispensingService::class);

        $expiringBatches = $fefoService->getExpiringBatches($this->business->id, 30);

        $this->assertCount(1, $expiringBatches);
        $this->assertEquals($stock1->id, $expiringBatches->first()->id);
    }

    #[Test]
    public function it_can_get_expired_batches()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $stock1 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 10,
            'expire_date' => now()->subDays(15), // Expired
        ]);

        $stock2 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 20,
            'expire_date' => now()->addDays(30), // Not expired
        ]);

        $fefoService = app(FEFODispensingService::class);

        $expiredBatches = $fefoService->getExpiredBatches($this->business->id);

        $this->assertCount(1, $expiredBatches);
        $this->assertEquals($stock1->id, $expiredBatches->first()->id);
    }

    #[Test]
    public function it_can_get_batch_movement_history()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 50,
        ]);

        // Create some movements
        StockMovement::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'user_id' => $this->user->id,
            'movement_type' => 'in',
            'quantity' => 20,
            'created_at' => now()->subDays(5),
        ]);

        StockMovement::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'user_id' => $this->user->id,
            'movement_type' => 'out',
            'quantity' => 10,
            'created_at' => now()->subDays(2),
        ]);

        $fefoService = app(FEFODispensingService::class);

        $movementHistory = $fefoService->getBatchMovementHistory($stock->id);

        $this->assertCount(2, $movementHistory->stockMovements);
        $this->assertEquals('out', $movementHistory->stockMovements->first()->movement_type); // Most recent first
    }
}
