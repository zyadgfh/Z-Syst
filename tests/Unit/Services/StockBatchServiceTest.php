<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Services\StockBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockBatchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockBatchService $service;
    protected Business $business;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $this->business->id]);
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);

        $this->service = new StockBatchService();
    }

    public function test_get_expiring_batches_returns_collection(): void
    {
        $result = $this->service->getExpiringBatches($this->business->id);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_get_expired_batches_returns_collection(): void
    {
        $result = $this->service->getExpiredBatches($this->business->id);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }
}
