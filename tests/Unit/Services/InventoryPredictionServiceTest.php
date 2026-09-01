<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Product;
use App\Services\AI\InventoryPredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryPredictionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryPredictionService $service;
    protected Business $business;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);

        $this->service = new InventoryPredictionService();
    }

    public function test_predict_demand_returns_array(): void
    {
        $result = $this->service->predictDemand($this->product->id, $this->business->id);

        $this->assertIsArray($result);
    }

    public function test_get_products_needing_reorder_returns_collection(): void
    {
        $result = $this->service->getProductsNeedingReorder($this->business->id);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }
}
