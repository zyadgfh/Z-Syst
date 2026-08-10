<?php

namespace Tests\Integration;

use App\Models\Product;
use App\Models\Stock;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = app(ProductService::class);
    }

    public function test_create_product_with_stock(): void
    {
        $businessId = 1;
        $data = [
            'productName' => 'Test Product',
            'category_id' => 1,
            'productCode' => 'TEST001',
            'purchase_with_tax' => 100.00,
            'sales_price' => 150.00,
            'batch_no' => 'BATCH001',
            'expire_date' => '2024-12-31',
            'qty' => 50,
        ];

        $product = $this->productService->createProduct($data, $businessId);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'productName' => 'Test Product',
            'business_id' => $businessId,
        ]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'business_id' => $businessId,
            'batch_no' => 'BATCH001',
            'productStock' => 50,
        ]);
    }

    public function test_update_product_stock(): void
    {
        $businessId = 1;
        
        // Create initial product
        $product = Product::factory()->create(['business_id' => $businessId]);
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'business_id' => $businessId,
            'productStock' => 100,
            'batch_no' => 'BATCH001',
        ]);

        $data = [
            'qty' => 25,
            'batch_no' => 'BATCH001',
        ];

        $updatedProduct = $this->productService->updateStock($product->id, $data, $businessId);

        $this->assertEquals(125, Stock::where('product_id', $product->id)->first()->productStock);
    }

    public function test_update_stock_insufficient_quantity(): void
    {
        $this->expectException(\App\Exceptions\InsufficientStockException::class);

        $businessId = 1;
        
        $product = Product::factory()->create(['business_id' => $businessId]);
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'business_id' => $businessId,
            'productStock' => 10,
            'batch_no' => 'BATCH001',
        ]);

        $data = [
            'qty' => -25, // Try to remove more than available
            'batch_no' => 'BATCH001',
        ];

        $this->productService->updateStock($product->id, $data, $businessId);
    }

    public function test_delete_product_with_images(): void
    {
        $product = Product::factory()->create([
            'images' => ['image1.jpg', 'image2.jpg'],
        ]);

        // Mock storage
        Storage::shouldReceive('exists')->andReturn(true);
        Storage::shouldReceive('delete')->twice();

        $result = $this->productService->deleteProduct($product);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_get_products_with_stock_filters(): void
    {
        $businessId = 1;
        
        // Create products with different stock levels
        $product1 = Product::factory()->create(['business_id' => $businessId, 'productName' => 'Product A']);
        $product2 = Product::factory()->create(['business_id' => $businessId, 'productName' => 'Product B']);
        
        Stock::factory()->create([
            'product_id' => $product1->id,
            'business_id' => $businessId,
            'productStock' => 50,
            'batch_no' => 'BATCH001',
        ]);
        
        Stock::factory()->create([
            'product_id' => $product2->id,
            'business_id' => $businessId,
            'productStock' => 0,
            'batch_no' => 'BATCH002',
        ]);

        // Test with check_stock filter
        $result = $this->productService->getProductsWithStock(['check_stock' => 'true'], $businessId, 10);

        $this->assertCount(1, $result->items());
        $this->assertEquals('Product A', $result->items()[0]->product->productName);
    }
}