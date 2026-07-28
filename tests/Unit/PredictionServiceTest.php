<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\PredictionSetting;
use App\Models\SalesForecast;
use App\Services\PredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PredictionService $predictionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->predictionService = app(PredictionService::class);
    }

    public function test_forecast_product_returns_forecast_data()
    {
        $business = Business::factory()->create();
        PredictionSetting::getForBusiness($business->id);
        $product = Product::factory()->create(['business_id' => $business->id, 'sales_price' => 100]);

        // Create some historical sales data
        $sale = Sale::factory()->create([
            'business_id' => $business->id,
            'saleDate' => now()->subDays(5)->format('Y-m-d'),
        ]);
        SaleDetails::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantities' => 10,
            'price' => 100,
        ]);

        $result = $this->predictionService->forecastProduct($product->id, $business->id, 7);

        $this->assertArrayHasKey('product_id', $result);
        $this->assertEquals($product->id, $result['product_id']);
        $this->assertArrayHasKey('forecasts', $result);
        $this->assertCount(7, $result['forecasts']);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('total_predicted_qty', $result['summary']);
        $this->assertArrayHasKey('total_predicted_revenue', $result['summary']);
    }

    public function test_forecast_product_creates_forecast_records()
    {
        $business = Business::factory()->create();
        PredictionSetting::getForBusiness($business->id);
        $product = Product::factory()->create(['business_id' => $business->id]);

        $this->predictionService->forecastProduct($product->id, $business->id, 5);

        $forecasts = SalesForecast::where('business_id', $business->id)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->get();

        $this->assertCount(5, $forecasts);
    }

    public function test_calculate_reorder_point_returns_expected_structure()
    {
        $business = Business::factory()->create();
        PredictionSetting::getForBusiness($business->id);
        $product = Product::factory()->create(['business_id' => $business->id]);

        $result = $this->predictionService->calculateReorderPoint($product->id, $business->id);

        $this->assertArrayHasKey('product_id', $result);
        $this->assertArrayHasKey('reorder_point', $result);
        $this->assertArrayHasKey('safety_stock', $result);
        $this->assertArrayHasKey('lead_time_demand', $result);
        $this->assertArrayHasKey('economic_order_qty', $result);
        $this->assertArrayHasKey('daily_average_demand', $result);
    }

    public function test_batch_forecast_returns_results_for_multiple_products()
    {
        $business = Business::factory()->create();
        PredictionSetting::getForBusiness($business->id);
        $product1 = Product::factory()->create(['business_id' => $business->id]);
        $product2 = Product::factory()->create(['business_id' => $business->id]);

        $results = $this->predictionService->batchForecast(
            [$product1->id, $product2->id],
            $business->id
        );

        $this->assertCount(2, $results);
        $this->assertEquals($product1->id, $results[0]['product_id']);
        $this->assertEquals($product2->id, $results[1]['product_id']);
    }

    public function test_forecast_all_products_forecasts_all_business_products()
    {
        $business = Business::factory()->create();
        PredictionSetting::getForBusiness($business->id);
        Product::factory(3)->create(['business_id' => $business->id]);

        $results = $this->predictionService->forecastAllProducts($business->id);

        $this->assertCount(3, $results);
    }

    public function test_get_demand_report_returns_expected_structure()
    {
        $business = Business::factory()->create();
        PredictionSetting::getForBusiness($business->id);
        $product = Product::factory()->create([
            'business_id' => $business->id,
            'sales_price' => 50,
        ]);

        // Create a forecast record
        SalesForecast::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'forecast_date' => now()->addDay(),
            'predicted_quantity' => 10,
            'predicted_revenue' => 500,
            'confidence_score' => 85,
            'method_used' => 'combined',
            'is_active' => true,
        ]);

        $report = $this->predictionService->getDemandReport($business->id, 'daily');

        $this->assertArrayHasKey('products', $report);
        $this->assertArrayHasKey('business_id', $report);
        $this->assertCount(1, $report['products']);
        $this->assertEquals($product->id, $report['products'][0]['product_id']);
    }
}