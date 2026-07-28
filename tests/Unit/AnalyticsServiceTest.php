<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $analyticsService;

    protected $user;

    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company and user
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Authenticate as the test user
        $this->actingAs($this->user);

        $this->analyticsService = new AnalyticsService;
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_it_can_get_dashboard_kpis(): void
    {
        // Create test data
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'cost_price' => 10,
            'retail_price' => 15,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'reorder_level' => 20,
        ]);

        $sale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 150,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'total' => 150,
        ]);

        $kpis = $this->analyticsService->getDashboardKPIs();

        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('total_sales', $kpis);
        $this->assertArrayHasKey('total_revenue', $kpis);
        $this->assertArrayHasKey('total_stock_value', $kpis);
        $this->assertArrayHasKey('low_stock_count', $kpis);
        $this->assertGreaterThanOrEqual(1, $kpis['total_sales']);
        $this->assertGreaterThanOrEqual(150, $kpis['total_revenue']);
    }

    public function test_it_can_get_inventory_summary(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'cost_price' => 10,
            'retail_price' => 15,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'reorder_level' => 20,
            'reorder_quantity' => 50,
        ]);

        $summary = $this->analyticsService->getInventorySummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_products', $summary);
        $this->assertArrayHasKey('total_quantity', $summary);
        $this->assertArrayHasKey('total_stock_value', $summary);
        $this->assertArrayHasKey('total_retail_value', $summary);
        $this->assertEquals(1, $summary['total_products']);
        $this->assertEquals(100, $summary['total_quantity']);
        $this->assertEquals(1000, $summary['total_stock_value']); // 100 * 10
        $this->assertEquals(1500, $summary['total_retail_value']); // 100 * 15
    }

    public function test_it_can_identify_low_stock_products(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'reorder_level' => 20,
            'reorder_quantity' => 50,
        ]);

        $lowStockProducts = $this->analyticsService->getLowStockProducts();

        $this->assertIsArray($lowStockProducts);
        $this->assertCount(1, $lowStockProducts);
        $this->assertEquals('high', $lowStockProducts[0]['urgency']);
    }

    public function test_it_can_identify_out_of_stock_products(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'quantity' => 0,
            'reorder_level' => 20,
        ]);

        $outOfStockProducts = $this->analyticsService->getOutOfStockProducts();

        $this->assertIsArray($outOfStockProducts);
        $this->assertCount(1, $outOfStockProducts);
        $this->assertEquals(0, $outOfStockProducts[0]['current_quantity']);
    }

    public function test_it_can_identify_expiring_products(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'cost_price' => 10,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'quantity' => 50,
            'expiry_date' => Carbon::now()->addDays(15),
        ]);

        $expiringProducts = $this->analyticsService->getExpiringProducts(30);

        $this->assertIsArray($expiringProducts);
        $this->assertCount(1, $expiringProducts);
        $this->assertEquals(500, $expiringProducts[0]['value']); // 50 * 10
    }

    public function test_it_can_get_sales_trends(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create sales over different days
        foreach (range(1, 5) as $day) {
            $sale = Sale::factory()->create([
                'company_id' => $this->company->id,
                'total_amount' => 100,
                'created_at' => Carbon::now()->subDays($day),
            ]);

            SaleItem::factory()->create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => 5,
                'total' => 100,
            ]);
        }

        $trends = $this->analyticsService->getSalesTrends('daily');

        $this->assertIsArray($trends);
        $this->assertGreaterThan(0, count($trends));
    }

    public function test_it_can_get_top_selling_products(): void
    {
        $product1 = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $product2 = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create sales for product 1
        $sale1 = Sale::factory()->create([
            'company_id' => $this->company->id,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale1->id,
            'product_id' => $product1->id,
            'quantity' => 50,
            'total' => 500,
        ]);

        // Create sales for product 2
        $sale2 = Sale::factory()->create([
            'company_id' => $this->company->id,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale2->id,
            'product_id' => $product2->id,
            'quantity' => 20,
            'total' => 200,
        ]);

        $topProducts = $this->analyticsService->getTopSellingProducts(10);

        $this->assertIsArray($topProducts);
        $this->assertCount(2, $topProducts);
        $this->assertEquals($product1->id, $topProducts[0]['product_id']); // Product 1 should be first
    }

    public function test_it_caches_kpis_results(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        // First call - should cache
        $kpis1 = $this->analyticsService->getDashboardKPIs();

        // Second call - should return cached result
        $kpis2 = $this->analyticsService->getDashboardKPIs();

        $this->assertEquals($kpis1, $kpis2);
    }

    public function test_it_respects_branch_scoping(): void
    {
        $branch1 = Branch::factory()->create(['company_id' => $this->company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $this->company->id]);

        // Create branch-specific user
        $branchUser = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch1->id,
        ]);

        $this->actingAs($branchUser);
        $branchAnalytics = new AnalyticsService;

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create stock for branch 1
        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'branch_id' => $branch1->id,
            'quantity' => 50,
        ]);

        // Create stock for branch 2
        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'branch_id' => $branch2->id,
            'quantity' => 100,
        ]);

        $summary = $branchAnalytics->getInventorySummary();

        // Should only see branch 1 stock
        $this->assertEquals(50, $summary['total_quantity']);
    }

    /*
    |--------------------------------------------------------------------------
    | Sales Analytics Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_get_sales_by_category(): void
    {
        $category1 = ProductCategory::factory()->create(['company_id' => $this->company->id]);
        $category2 = ProductCategory::factory()->create(['company_id' => $this->company->id]);

        $product1 = Product::factory()->create([
            'company_id' => $this->company->id,
            'product_category_id' => $category1->id,
        ]);
        $product2 = Product::factory()->create([
            'company_id' => $this->company->id,
            'product_category_id' => $category2->id,
        ]);

        $sale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 300,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product1->id,
            'quantity' => 10,
            'total' => 200,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product2->id,
            'quantity' => 5,
            'total' => 100,
        ]);

        $salesByCategory = $this->analyticsService->getSalesByCategory();

        $this->assertCount(2, $salesByCategory);
        $categoryNames = collect($salesByCategory)->pluck('category_name')->toArray();
        $this->assertContains($category1->name, $categoryNames);
        $this->assertContains($category2->name, $categoryNames);
    }

    public function test_it_can_get_sales_by_branch(): void
    {
        $branch1 = Branch::factory()->create(['company_id' => $this->company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $this->company->id]);

        // Create sales for branch 1
        Sale::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch1->id,
            'total_amount' => 100,
        ]);

        // Create sales for branch 2
        Sale::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch2->id,
            'total_amount' => 200,
        ]);

        $salesByBranch = $this->analyticsService->getSalesByBranch();

        $this->assertCount(2, $salesByBranch);
        $branchData = collect($salesByBranch)->keyBy('branch_id');
        $this->assertEquals(3, $branchData[$branch1->id]['total_sales']);
        $this->assertEquals(2, $branchData[$branch2->id]['total_sales']);
    }

    public function test_it_can_get_payment_method_breakdown(): void
    {
        // Create sales with different payment methods
        Sale::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'payment_method' => 'cash',
            'total_amount' => 100,
        ]);

        Sale::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'payment_method' => 'card',
            'total_amount' => 200,
        ]);

        Sale::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'payment_method' => 'insurance',
            'total_amount' => 300,
        ]);

        $breakdown = $this->analyticsService->getPaymentMethodBreakdown();

        $this->assertCount(3, $breakdown);
        $methods = collect($breakdown)->keyBy('payment_method');
        $this->assertEquals(5, $methods['cash']['count']);
        $this->assertEquals(3, $methods['card']['count']);
        $this->assertEquals(2, $methods['insurance']['count']);
        $this->assertEquals(50.0, $methods['cash']['percentage']); // 5/10 * 100
        $this->assertEquals(30.0, $methods['card']['percentage']); // 3/10 * 100
        $this->assertEquals(20.0, $methods['insurance']['percentage']); // 2/10 * 100
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory Analytics Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_get_dead_stock(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'cost_price' => 10,
        ]);

        // Create stock with quantity but no recent sales
        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        // Create a sale that is older than 90 days
        $oldSale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subDays(100),
        ]);

        SaleItem::factory()->create([
            'sale_id' => $oldSale->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $deadStock = $this->analyticsService->getDeadStock(90);

        $this->assertIsArray($deadStock);
        $this->assertCount(1, $deadStock);
        $this->assertEquals($product->id, $deadStock[0]['product_id']);
    }

    public function test_it_can_get_fast_moving_products(): void
    {
        $product1 = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $product2 = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create many sales for product 1 (fast moving)
        $sale1 = Sale::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        SaleItem::factory()->create([
            'sale_id' => $sale1->id,
            'product_id' => $product1->id,
            'quantity' => 100,
        ]);

        // Create few sales for product 2 (slow moving)
        $sale2 = Sale::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        SaleItem::factory()->create([
            'sale_id' => $sale2->id,
            'product_id' => $product2->id,
            'quantity' => 10,
        ]);

        $fastMoving = $this->analyticsService->getFastMovingProducts(30, 20);

        $this->assertCount(2, $fastMoving);
        $this->assertEquals($product1->id, $fastMoving[0]['product_id']);
        $this->assertGreaterThan($fastMoving[1]['average_daily_sales'], $fastMoving[0]['average_daily_sales']);
    }

    public function test_it_can_get_stock_turnover_analysis(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'cost_price' => 10,
        ]);

        // Create stock with low quantity relative to sales
        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        // Create high sales volume to drive turnover rate up
        for ($i = 0; $i < 10; $i++) {
            $sale = Sale::factory()->create([
                'company_id' => $this->company->id,
                'created_at' => Carbon::now()->subDays($i * 5),
            ]);
            SaleItem::factory()->create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => 5,
            ]);
        }

        $turnover = $this->analyticsService->getStockTurnover(90);

        $this->assertIsArray($turnover);
        $this->assertCount(1, $turnover);
        $this->assertEquals($product->id, $turnover[0]['product_id']);
        $this->assertGreaterThan(0, $turnover[0]['turnover_rate']);
        $this->assertContains($turnover[0]['turnover_category'], ['fast', 'moderate', 'slow', 'dead']);
    }

    /*
    |--------------------------------------------------------------------------
    | Transfer Analytics Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_get_transfer_trends(): void
    {
        $branch1 = Branch::factory()->create(['company_id' => $this->company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $this->company->id]);

        // Create transfers over different days
        foreach (range(1, 3) as $day) {
            StockTransfer::factory()->create([
                'company_id' => $this->company->id,
                'from_branch_id' => $branch1->id,
                'to_branch_id' => $branch2->id,
                'requested_by' => $this->user->id,
                'status' => 'pending',
                'total_items' => 5,
                'total_quantity' => 50,
                'total_value' => 500,
                'created_at' => Carbon::now()->subDays($day),
            ]);
        }

        $trends = $this->analyticsService->getTransferTrends('daily');

        $this->assertIsArray($trends);
        $this->assertGreaterThan(0, count($trends));
    }

    public function test_it_can_get_transfers_by_branch(): void
    {
        $branch1 = Branch::factory()->create(['company_id' => $this->company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $this->company->id]);
        $branch3 = Branch::factory()->create(['company_id' => $this->company->id]);

        // Create transfers from branch1 -> branch2
        StockTransfer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $branch1->id,
            'to_branch_id' => $branch2->id,
            'requested_by' => $this->user->id,
            'total_value' => 100,
            'total_quantity' => 10,
        ]);

        // Create transfers from branch1 -> branch3
        StockTransfer::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $branch1->id,
            'to_branch_id' => $branch3->id,
            'requested_by' => $this->user->id,
            'total_value' => 200,
            'total_quantity' => 20,
        ]);

        $transfersByBranch = $this->analyticsService->getTransfersByBranch();

        $this->assertCount(2, $transfersByBranch);
        $this->assertEquals(3, $transfersByBranch[0]['total_transfers']);
    }

    public function test_it_can_get_most_transferred_products(): void
    {
        $product1 = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $product2 = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $branch1 = Branch::factory()->create(['company_id' => $this->company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $this->company->id]);

        // Create a stock transfer
        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $branch1->id,
            'to_branch_id' => $branch2->id,
            'requested_by' => $this->user->id,
            'status' => 'received',
        ]);

        // Add items - product1 transferred more
        StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $product1->id,
            'quantity_requested' => 100,
            'quantity_sent' => 100,
            'quantity_received' => 100,
            'unit_cost' => 10,
            'total_cost' => 1000,
        ]);

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $product2->id,
            'quantity_requested' => 20,
            'quantity_sent' => 20,
            'quantity_received' => 20,
            'unit_cost' => 10,
            'total_cost' => 200,
        ]);

        $mostTransferred = $this->analyticsService->getMostTransferredProducts(10);

        $this->assertCount(2, $mostTransferred);
        $this->assertEquals($product1->id, $mostTransferred[0]['product_id']);
        $this->assertEquals(100, $mostTransferred[0]['total_quantity_sent']);
    }

    public function test_it_can_get_transfer_performance_metrics(): void
    {
        $branch1 = Branch::factory()->create(['company_id' => $this->company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $this->company->id]);

        // Create completed transfers
        StockTransfer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $branch1->id,
            'to_branch_id' => $branch2->id,
            'requested_by' => $this->user->id,
            'status' => 'received',
            'requested_at' => Carbon::now()->subDays(2),
            'received_at' => Carbon::now()->subDay(),
            'total_value' => 500,
            'total_quantity' => 50,
        ]);

        // Create pending transfers
        StockTransfer::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $branch1->id,
            'to_branch_id' => $branch2->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'total_value' => 300,
            'total_quantity' => 30,
        ]);

        $metrics = $this->analyticsService->getTransferPerformanceMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_transfers', $metrics);
        $this->assertArrayHasKey('completed_transfers', $metrics);
        $this->assertArrayHasKey('pending_transfers', $metrics);
        $this->assertArrayHasKey('completion_rate', $metrics);
        $this->assertEquals(5, $metrics['total_transfers']);
        $this->assertEquals(3, $metrics['completed_transfers']);
        $this->assertEquals(2, $metrics['pending_transfers']);
        $this->assertEquals(60.0, $metrics['completion_rate']); // 3/5 * 100
    }

    /*
    |--------------------------------------------------------------------------
    | Cache & Context Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_clear_cache(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        // First call caches the result
        $kpis1 = $this->analyticsService->getDashboardKPIs();
        $this->assertNotEmpty($kpis1);

        // Clear cache
        $result = $this->analyticsService->clearCache();

        $this->assertNull($result);
    }

    public function test_it_can_set_context_manually(): void
    {
        $service = new AnalyticsService(null, null);

        // Initially no company context (since no auth)
        $this->assertNull($service->getCompanyId());

        // Set context manually
        $service->setContext($this->company->id, 1);

        $this->assertEquals($this->company->id, $service->getCompanyId());
    }

    /*
    |--------------------------------------------------------------------------
    | Edge Case Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_handles_empty_data_gracefully(): void
    {
        // Test KPIs with no data
        $kpis = $this->analyticsService->getDashboardKPIs();
        $this->assertEquals(0, $kpis['total_sales']);
        $this->assertEquals(0, $kpis['total_revenue']);
        $this->assertEquals(0, $kpis['average_transaction_value']);

        // Test sales trends with no data
        $trends = $this->analyticsService->getSalesTrends('daily');
        $this->assertIsArray($trends);
        $this->assertEmpty($trends);

        // Test top selling products with no data
        $topProducts = $this->analyticsService->getTopSellingProducts(10);
        $this->assertIsArray($topProducts);
        $this->assertEmpty($topProducts);

        // Test sales by category with no data
        $salesByCategory = $this->analyticsService->getSalesByCategory();
        $this->assertCount(0, $salesByCategory);

        // Test low stock products with no data
        $lowStock = $this->analyticsService->getLowStockProducts();
        $this->assertIsArray($lowStock);
        $this->assertEmpty($lowStock);

        // Test out of stock products with no data
        $outOfStock = $this->analyticsService->getOutOfStockProducts();
        $this->assertIsArray($outOfStock);
        $this->assertEmpty($outOfStock);

        // Test transfer performance with no data
        $metrics = $this->analyticsService->getTransferPerformanceMetrics();
        $this->assertEquals(0, $metrics['total_transfers']);
        $this->assertEquals(0, $metrics['completion_rate']);
        $this->assertEquals(0, $metrics['average_completion_time_hours']);
    }

    public function test_it_handles_different_date_ranges(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'cost_price' => 10,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        // Create a sale from last month
        $lastMonthSale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 500,
            'created_at' => Carbon::now()->subMonth(),
        ]);
        SaleItem::factory()->create([
            'sale_id' => $lastMonthSale->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        // Create a sale from last week
        $lastWeekSale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 300,
            'created_at' => Carbon::now()->subWeek(),
        ]);
        SaleItem::factory()->create([
            'sale_id' => $lastWeekSale->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        // Test with custom date range covering only last month
        $startDate = Carbon::now()->subMonth()->startOfMonth();
        $endDate = Carbon::now()->subMonth()->endOfMonth();

        $monthKpis = $this->analyticsService->getDashboardKPIs($startDate, $endDate);
        $this->assertEquals(1, $monthKpis['total_sales']);
        $this->assertEquals(500, $monthKpis['total_revenue']);

        // Test with custom date range covering last week
        $weekStart = Carbon::now()->subWeek()->startOfWeek();
        $weekEnd = Carbon::now()->subWeek()->endOfWeek();

        $weekKpis = $this->analyticsService->getDashboardKPIs($weekStart, $weekEnd);
        $this->assertEquals(1, $weekKpis['total_sales']);
        $this->assertEquals(300, $weekKpis['total_revenue']);

        // Test with date range that should yield no results
        $futureStart = Carbon::now()->addYear();
        $futureEnd = Carbon::now()->addYear()->endOfYear();

        $futureKpis = $this->analyticsService->getDashboardKPIs($futureStart, $futureEnd);
        $this->assertEquals(0, $futureKpis['total_sales']);
        $this->assertEquals(0, $futureKpis['total_revenue']);
    }
}
