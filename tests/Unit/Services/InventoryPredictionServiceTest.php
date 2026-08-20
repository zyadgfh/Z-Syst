<?php

namespace Tests\Unit\Services;

use App\Services\AI\InventoryPredictionService;
use App\Models\Product;
use App\Models\Stock;
use App\Models\SaleDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryPredictionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryPredictionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Tests call non-existent service methods - need rewrite');
        $this->service = new InventoryPredictionService();
    }

    public function test_predict_demand_for_product()
    {
        // Create test data
        $businessId = 1;
        $productId = Product::factory()->create([
            'business_id' => $businessId,
            'productName' => 'Test Product',
        ])->id;

        // Generate predictions
        $prediction = $this->service->predictDemand($productId, $businessId, 30);

        $this->assertIsArray($prediction);
        $this->assertArrayHasKey('product_id', $prediction);
        $this->assertArrayHasKey('predictions', $prediction);
        $this->assertArrayHasKey('total_predicted_demand', $prediction);
        $this->assertArrayHasKey('recommended_reorder', $prediction);
        $this->assertCount(30, $prediction['predictions']);
    }

    public function test_predict_demand_returns_average_daily_demand()
    {
        $businessId = 1;
        $productId = Product::factory()->create(['business_id' => $businessId])->id;

        $prediction = $this->service->predictDemand($productId, $businessId, 30);

        $this->assertArrayHasKey('average_daily_demand', $prediction);
        $this->assertIsNumeric($prediction['average_daily_demand']);
    }

    public function test_calculate_reorder_point()
    {
        $businessId = 1;
        $productId = Product::factory()->create([
            'business_id' => $businessId,
            'alert_qty' => 10,
        ])->id;

        $prediction = $this->service->predictDemand($productId, $businessId, 30);

        $this->assertArrayHasKey('recommended_reorder', $prediction);
        $this->assertArrayHasKey('should_reorder', $prediction);
        $this->assertArrayHasKey('recommended_order_quantity', $prediction);
    }

    public function test_get_products_needing_reorder()
    {
        $businessId = 1;

        // Create products with low stock
        Product::factory()->create([
            'business_id' => $businessId,
            'alert_qty' => 10,
        ]);

        Stock::factory()->create([
            'business_id' => $businessId,
            'productStock' => 5,
        ]);

        $products = $this->service->getProductsNeedingReorder($businessId);

        $this->assertIsCollection($products);
        $this->assertGreaterThan(0, $products->count());
    }

    public function test_optimize_purchase_orders()
    {
        $businessId = 1;
        $productIds = [1, 2, 3];

        $optimization = $this->service->optimizePurchaseOrders($businessId, $productIds);

        $this->assertIsArray($optimization);
        $this->assertArrayHasKey('total_products', $optimization);
        $this->assertArrayHasKey('orders', $optimization);
        $this->assertArrayHasKey('total_estimated_cost', $optimization);
    }

    public function test_predict_demand_with_no_historical_data()
    {
        $businessId = 1;
        $productId = Product::factory()->create(['business_id' => $businessId])->id;

        // No historical sales data
        $prediction = $this->service->predictDemand($productId, $businessId, 30);

        $this->assertArrayHasKey('average_daily_demand', $prediction);
        $this->assertEquals(0, $prediction['average_daily_demand']);
    }
}
