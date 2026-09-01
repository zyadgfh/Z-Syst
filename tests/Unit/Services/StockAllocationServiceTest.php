<?php

namespace Tests\Unit\Services;

use App\Exceptions\StockUnavailableException;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Stock\StockAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockAllocationService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StockAllocationService();
        $this->user = User::factory()->create();
    }

    public function test_allocate_reduces_stock_quantity(): void
    {
        $stock = Stock::factory()->create(['productStock' => 100]);

        $movement = $this->service->allocate(
            $stock,
            20,
            'App\\Models\\Sale',
            1,
            $this->user->id,
            'Test allocation'
        );

        $this->assertEquals(80, $stock->fresh()->productStock);
        $this->assertEquals('out', $movement->movement_type);
        $this->assertEquals(20, $movement->quantity);
        $this->assertEquals(100, $movement->before_quantity);
        $this->assertEquals(80, $movement->after_quantity);
        $this->assertEquals($stock->business_id, $movement->business_id);
        $this->assertEquals($stock->product_id, $movement->product_id);
    }

    public function test_allocate_throws_exception_when_insufficient_stock(): void
    {
        $stock = Stock::factory()->create(['productStock' => 5]);

        $this->expectException(StockUnavailableException::class);

        $this->service->allocate($stock, 10, 'App\\Models\\Sale', 1, $this->user->id);
    }

    public function test_release_increases_stock_quantity(): void
    {
        $stock = Stock::factory()->create(['productStock' => 50]);

        $movement = $this->service->release(
            $stock,
            10,
            'App\\Models\\SaleReturn',
            1,
            $this->user->id,
            'Return'
        );

        $this->assertEquals(60, $stock->fresh()->productStock);
        $this->assertEquals('in', $movement->movement_type);
        $this->assertEquals(10, $movement->quantity);
    }

    public function test_add_stock_increases_stock_quantity(): void
    {
        $stock = Stock::factory()->create(['productStock' => 50]);

        $movement = $this->service->addStock(
            $stock,
            30,
            'App\\Models\\Purchase',
            1,
            $this->user->id,
            'New purchase'
        );

        $this->assertEquals(80, $stock->fresh()->productStock);
        $this->assertEquals('in', $movement->movement_type);
        $this->assertEquals(30, $movement->quantity);
    }

    public function test_adjust_stock_sets_exact_quantity(): void
    {
        $stock = Stock::factory()->create(['productStock' => 50]);

        $movement = $this->service->adjustStock(
            $stock,
            75,
            'App\\Models\\StockAudit',
            1,
            $this->user->id,
            'Audit adjustment'
        );

        $this->assertEquals(75, $stock->fresh()->productStock);
        $this->assertEquals('adjustment', $movement->movement_type);
        $this->assertEquals(25, $movement->quantity);
    }

    public function test_movement_records_batch_info(): void
    {
        $stock = Stock::factory()->create([
            'productStock' => 100,
            'batch_no' => 'BAT-001',
            'expire_date' => '2027-01-01',
        ]);

        $movement = $this->service->allocate($stock, 10, 'App\\Models\\Sale', 1, $this->user->id);

        $this->assertEquals('BAT-001', $movement->batch_no);
    }
}
