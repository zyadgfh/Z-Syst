<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_stock()
    {
        $business = Business::factory()->create();
        $product = Product::factory()->create(['business_id' => $business->id]);

        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'business_id' => $business->id,
        ]);

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id,
            'batch_no' => $stock->batch_no,
        ]);
    }

    public function test_stock_has_fillable_attributes()
    {
        $fillable = (new Stock)->getFillable();

        $this->assertContains('product_id', $fillable);
        $this->assertContains('business_id', $fillable);
        $this->assertContains('productStock', $fillable);
        $this->assertContains('batch_no', $fillable);
        $this->assertContains('expire_date', $fillable);
    }

    public function test_stock_belongs_to_product()
    {
        $product = Product::factory()->create();
        $stock = Stock::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $stock->product);
        $this->assertEquals($product->id, $stock->product->id);
    }

    public function test_stock_with_expired_date()
    {
        $stock = Stock::factory()->expired()->create();

        $this->assertNotNull($stock->expire_date);
        $this->assertTrue(now()->isAfter($stock->expire_date));
    }

    public function test_stock_expiring_soon()
    {
        $stock = Stock::factory()->expiringSoon(5)->create();

        $this->assertNotNull($stock->expire_date);
        $this->assertTrue(now()->diffInDays($stock->expire_date) <= 5);
    }

    public function test_stock_can_be_deleted()
    {
        $stock = Stock::factory()->create();
        $stockId = $stock->id;

        $stock->delete();

        $this->assertSoftDeleted('stocks', ['id' => $stockId]);
    }
}
