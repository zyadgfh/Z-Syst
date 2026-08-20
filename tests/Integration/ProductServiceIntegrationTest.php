<?php

namespace Tests\Integration;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;
    private Business $business;
    private Category $category;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productService = app(ProductService::class);

        $businessCategory = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create([
            'business_category_id' => $businessCategory->id,
        ]);

        $this->category = Category::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
        ]);

        // Authenticate as this user so auth()->id() is available for stock movements
        $this->actingAs($this->user);
    }

    public function test_create_product_with_stock(): void
    {
        $data = [
            'productName' => 'Test Product',
            'category_id' => $this->category->id,
            'productCode' => 'TEST001',
            'purchase_with_tax' => 100.00,
            'sales_price' => 150.00,
            'batch_no' => 'BATCH001',
            'expire_date' => '2027-12-31',
            'qty' => 50,
        ];

        $product = $this->productService->createProduct($data, $this->business->id);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'productName' => 'Test Product',
            'business_id' => $this->business->id,
        ]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'business_id' => $this->business->id,
            'batch_no' => 'BATCH001',
            'productStock' => 50,
        ]);
    }

    public function test_update_product_stock(): void
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'business_id' => $this->business->id,
            'productStock' => 100,
            'batch_no' => 'BATCH001',
        ]);

        $data = [
            'qty' => 25,
            'batch_no' => 'BATCH001',
        ];

        $this->productService->updateStock($product->id, $data, $this->business->id);

        $this->assertEquals(125, Stock::where('product_id', $product->id)->first()->productStock);
    }

    public function test_update_stock_insufficient_quantity(): void
    {
        $this->expectException(\App\Exceptions\InsufficientStockException::class);

        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'business_id' => $this->business->id,
            'productStock' => 10,
            'batch_no' => 'BATCH001',
        ]);

        $data = [
            'qty' => -25, // Try to remove more than available
            'batch_no' => 'BATCH001',
        ];

        $this->productService->updateStock($product->id, $data, $this->business->id);
    }

    public function test_delete_product_with_images(): void
    {
        $product = Product::factory()->create([
            'images' => ['image1.jpg', 'image2.jpg'],
        ]);

        // Mock storage
        Storage::shouldReceive('exists')->once()->with('image1.jpg')->andReturn(true);
        Storage::shouldReceive('exists')->once()->with('image2.jpg')->andReturn(true);
        Storage::shouldReceive('delete')->once()->with('image1.jpg');
        Storage::shouldReceive('delete')->once()->with('image2.jpg');

        $result = $this->productService->deleteProduct($product);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_get_products_with_stock_filters(): void
    {
        $product1 = Product::factory()->create(['business_id' => $this->business->id, 'productName' => 'Product A']);
        $product2 = Product::factory()->create(['business_id' => $this->business->id, 'productName' => 'Product B']);

        Stock::factory()->create([
            'product_id' => $product1->id,
            'business_id' => $this->business->id,
            'productStock' => 50,
            'batch_no' => 'BATCH001',
        ]);

        Stock::factory()->create([
            'product_id' => $product2->id,
            'business_id' => $this->business->id,
            'productStock' => 0,
            'batch_no' => 'BATCH002',
        ]);

        // Test with check_stock filter
        $result = $this->productService->getProductsWithStock(['check_stock' => 'true'], $this->business->id, 10);

        $this->assertCount(1, $result->items());
        $this->assertEquals('Product A', $result->items()[0]->product->productName);
    }
}
