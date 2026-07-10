<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
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
}
