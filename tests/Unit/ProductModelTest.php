<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Manufacturer;
use App\Models\Stock;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_product()
    {
        $business = Business::factory()->create();
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();
        $manufacturer = Manufacturer::factory()->create();

        $product = Product::factory()->create([
            'business_id' => $business->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'manufacturer_id' => $manufacturer->id,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'productName' => $product->productName,
        ]);
    }

    public function test_product_has_fillable_attributes()
    {
        $fillable = (new Product())->getFillable();

        $this->assertContains('productName', $fillable);
        $this->assertContains('business_id', $fillable);
        $this->assertContains('category_id', $fillable);
        $this->assertContains('unit_id', $fillable);
        $this->assertContains('manufacturer_id', $fillable);
        $this->assertContains('sales_price', $fillable);
        $this->assertContains('productCode', $fillable);
    }

    public function test_product_has_casts()
    {
        $casts = (new Product())->getCasts();

        $this->assertArrayHasKey('meta', $casts);
        $this->assertEquals('json', $casts['meta']);
        $this->assertArrayHasKey('images', $casts);
        $this->assertEquals('json', $casts['images']);
    }

    public function test_product_belongs_to_category()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    public function test_product_belongs_to_unit()
    {
        $unit = Unit::factory()->create();
        $product = Product::factory()->create(['unit_id' => $unit->id]);

        $this->assertInstanceOf(Unit::class, $product->unit);
        $this->assertEquals($unit->id, $product->unit->id);
    }

    public function test_product_belongs_to_manufacturer()
    {
        $manufacturer = Manufacturer::factory()->create();
        $product = Product::factory()->create(['manufacturer_id' => $manufacturer->id]);

        $this->assertInstanceOf(Manufacturer::class, $product->manufacterer);
        $this->assertEquals($manufacturer->id, $product->manufacterer->id);
    }

    public function test_product_has_many_stocks()
    {
        $product = Product::factory()->create();
        $stock1 = Stock::factory()->create(['product_id' => $product->id, 'productStock' => 100]);
        $stock2 = Stock::factory()->create(['product_id' => $product->id, 'productStock' => 50]);

        $this->assertCount(2, $product->stocks);
    }

    public function test_product_fefo_stocks_ordered_by_expiry()
    {
        $product = Product::factory()->create();

        $stockFar = Stock::factory()->create([
            'product_id' => $product->id,
            'productStock' => 100,
            'expire_date' => now()->addYear()->format('Y-m-d'),
        ]);

        $stockNear = Stock::factory()->create([
            'product_id' => $product->id,
            'productStock' => 100,
            'expire_date' => now()->addMonth()->format('Y-m-d'),
        ]);

        $fefoStocks = $product->fefoStocks;
        $this->assertEquals($stockNear->id, $fefoStocks->first()->id);
    }

    public function test_product_scope_expiring_soon()
    {
        $product = Product::factory()->create();
        Stock::factory()->expiringSoon(10)->create(['product_id' => $product->id]);

        $expiringProducts = Product::expiringSoon(30)->get();

        $this->assertTrue($expiringProducts->contains($product->id));
    }

    public function test_product_scope_has_expired()
    {
        $product = Product::factory()->create();
        Stock::factory()->expired()->create(['product_id' => $product->id]);

        $expiredProducts = Product::hasExpired()->get();

        $this->assertTrue($expiredProducts->contains($product->id));
    }
}