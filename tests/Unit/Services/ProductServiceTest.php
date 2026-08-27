<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $service;
    protected User $user;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        Auth::shouldReceive('id')->andReturn($this->user->id);

        $this->service = app(ProductService::class);
    }

    public function test_list_returns_paginated_products(): void
    {
        Product::factory()->count(5)->create(['business_id' => $this->business->id]);

        $result = $this->service->list([], $this->business->id, 10);

        $this->assertEquals(5, $result->total());
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_list_filters_by_search(): void
    {
        Product::factory()->create(['business_id' => $this->business->id, 'productName' => 'Amoxicillin']);
        Product::factory()->create(['business_id' => $this->business->id, 'productName' => 'Ibuprofen']);

        $result = $this->service->list(['search' => 'Amox'], $this->business->id);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Amoxicillin', $result->first()->productName);
    }

    public function test_list_filters_by_category(): void
    {
        $category = Category::factory()->create(['business_id' => $this->business->id]);
        Product::factory()->create(['business_id' => $this->business->id, 'category_id' => $category->id]);
        Product::factory()->create(['business_id' => $this->business->id]);

        $result = $this->service->list(['category_id' => $category->id], $this->business->id);

        $this->assertEquals(1, $result->total());
    }

    public function test_list_excludes_other_businesses(): void
    {
        $otherBusiness = Business::factory()->create();
        Product::factory()->create(['business_id' => $this->business->id]);
        Product::factory()->create(['business_id' => $otherBusiness->id]);

        $result = $this->service->list([], $this->business->id);

        $this->assertEquals(1, $result->total());
    }

    public function test_list_sorts_by_field(): void
    {
        Product::factory()->create(['business_id' => $this->business->id, 'productName' => 'Z Product']);
        Product::factory()->create(['business_id' => $this->business->id, 'productName' => 'A Product']);

        $result = $this->service->list(['sort' => 'productName', 'direction' => 'asc'], $this->business->id);

        $this->assertEquals('A Product', $result->first()->productName);
    }

    public function test_show_returns_product_with_relations(): void
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $result = $this->service->show($product->id, $this->business->id);

        $this->assertEquals($product->id, $result->id);
        $this->assertArrayHasKey('stocks_sum_productstock', $result->toArray());
    }

    public function test_show_throws_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->show(99999, $this->business->id);
    }

    public function test_create_product_creates_stock(): void
    {
        $category = Category::factory()->create(['business_id' => $this->business->id]);
        $unit = Unit::factory()->create(['business_id' => $this->business->id]);

        $data = [
            'productName' => 'Test Product',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'purchase_without_tax' => 10.00,
            'purchase_with_tax' => 11.50,
            'sales_price' => 15.00,
            'qty' => 50,
            'batch_no' => 'BAT-TEST-001',
        ];

        $product = $this->service->createProduct($data, $this->business->id);

        $this->assertEquals('Test Product', $product->productName);
        $this->assertEquals($this->business->id, $product->business_id);

        $stock = Stock::where('product_id', $product->id)->first();
        $this->assertNotNull($stock);
        $this->assertEquals(50, $stock->productStock);
    }

    public function test_create_product_generates_code_when_not_provided(): void
    {
        $category = Category::factory()->create(['business_id' => $this->business->id]);
        $unit = Unit::factory()->create(['business_id' => $this->business->id]);

        $data = [
            'productName' => 'Auto Code Product',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'purchase_without_tax' => 5.00,
            'purchase_with_tax' => 5.00,
            'sales_price' => 8.00,
        ];

        $product = $this->service->createProduct($data, $this->business->id);

        $this->assertNotEmpty($product->internal_code);
    }

    public function test_update_product_updates_fields(): void
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'sales_price' => 10.00,
        ]);

        $updated = $this->service->updateProduct($product, ['sales_price' => 12.00], $this->business->id);

        $this->assertEquals(12.00, $updated->sales_price);
    }

    public function test_update_product_adds_stock(): void
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        Stock::factory()->create(['product_id' => $product->id, 'business_id' => $this->business->id, 'productStock' => 10]);

        $updated = $this->service->updateProduct($product, ['qty' => 5], $this->business->id);

        $stock = Stock::where('product_id', $product->id)->first();
        $this->assertEquals(15, $stock->productStock);
    }

    public function test_delete_product_archives_when_has_transactions(): void
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'archived' => false,
        ]);

        // Create a real sale detail so hasTransactions() returns true
        $sale = Sale::factory()->create(['business_id' => $this->business->id]);
        SaleDetails::factory()->create([
            'product_id' => $product->id,
            'sale_id' => $sale->id,
        ]);

        $result = $this->service->deleteProduct($product);

        $this->assertTrue($result);
    }

    public function test_search_products_returns_matching(): void
    {
        Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'Paracetamol 500mg',
            'active' => true,
            'archived' => false,
        ]);
        Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'Ibuprofen 200mg',
            'active' => true,
            'archived' => false,
        ]);

        $results = $this->service->searchProducts('Paracetamol', $this->business->id);

        $this->assertEquals(1, $results->count());
        $this->assertEquals('Paracetamol 500mg', $results->first()->productName);
    }

    public function test_search_products_excludes_inactive(): void
    {
        Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'Inactive Product',
            'active' => false,
        ]);

        $results = $this->service->searchProducts('Inactive', $this->business->id);

        $this->assertEquals(0, $results->count());
    }

    public function test_get_product_kpis_returns_metrics(): void
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'purchase_with_tax' => 10.00,
            'sales_price' => 15.00,
        ]);
        Stock::factory()->create([
            'product_id' => $product->id,
            'business_id' => $this->business->id,
            'productStock' => 100,
        ]);

        $kpis = $this->service->getProductKPIs($product->id, $this->business->id);

        $this->assertEquals(100, $kpis['current_stock']);
        $this->assertEquals(10.00, $kpis['average_cost']);
    }

    public function test_export_products_returns_array(): void
    {
        Product::factory()->count(3)->create(['business_id' => $this->business->id]);

        $export = $this->service->exportProducts($this->business->id);

        $this->assertCount(3, $export);
        $this->assertArrayHasKey('Product Name', $export[0]);
        $this->assertArrayHasKey('Selling Price', $export[0]);
    }
}
