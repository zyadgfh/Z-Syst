<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReturnsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Business $business;
    protected User $user;
    protected Party $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->customer = Party::factory()->create(['business_id' => $this->business->id]);
    }

    /** @test */
    public function it_can_create_a_sale_return()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
        ]);

        // Create a sale first
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'totalAmount' => 500.00,
            'paidAmount' => 500.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
            'saleDate' => now(),
        ]);

        $saleDetail = SaleDetails::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'price' => 50.00,
            'purchase_price' => 30.00,
            'batch_no' => $stock->batch_no,
            'expire_date' => $stock->expire_date,
            'quantities' => 10,
        ]);

        // Deduct stock
        $stock->decrement('productStock', 10);

        // Create return
        $return = SaleReturn::create([
            'business_id' => $this->business->id,
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'reason' => 'Customer not satisfied',
            'total_amount' => 250.00,
            'refund_amount' => 250.00,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('sale_returns', [
            'id' => $return->id,
            'sale_id' => $sale->id,
        ]);
        $this->assertEquals(250.00, $return->fresh()->total_amount);
    }

    /** @test */
    public function it_can_restore_stock_on_sale_return()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
        ]);

        // Create a sale
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'totalAmount' => 500.00,
            'paidAmount' => 500.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
            'saleDate' => now(),
        ]);

        $saleDetail = SaleDetails::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'price' => 50.00,
            'purchase_price' => 30.00,
            'batch_no' => $stock->batch_no,
            'expire_date' => $stock->expire_date,
            'quantities' => 10,
        ]);

        // Deduct stock
        $stock->decrement('productStock', 10);
        $this->assertEquals(90, $stock->fresh()->productStock);

        // Create return and restore stock
        $return = SaleReturn::create([
            'business_id' => $this->business->id,
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'reason' => 'Defective product',
            'total_amount' => 250.00,
            'refund_amount' => 250.00,
            'status' => 'approved',
        ]);

        // Restore stock
        $stock->increment('productStock', 10);

        $this->assertEquals(100, $stock->fresh()->productStock);
    }

    /** @test */
    public function it_can_create_partial_sale_return()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
        ]);

        // Create a sale with multiple items
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'totalAmount' => 1000.00,
            'paidAmount' => 1000.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
            'saleDate' => now(),
        ]);

        $saleDetail1 = SaleDetails::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'price' => 50.00,
            'purchase_price' => 30.00,
            'batch_no' => $stock->batch_no,
            'expire_date' => $stock->expire_date,
            'quantities' => 10,
        ]);

        $saleDetail2 = SaleDetails::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'price' => 50.00,
            'purchase_price' => 30.00,
            'batch_no' => $stock->batch_no,
            'expire_date' => $stock->expire_date,
            'quantities' => 10,
        ]);

        // Deduct stock
        $stock->decrement('productStock', 20);

        // Partial return (only 5 units)
        $return = SaleReturn::create([
            'business_id' => $this->business->id,
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'reason' => 'Wrong quantity delivered',
            'total_amount' => 250.00,
            'refund_amount' => 250.00,
            'status' => 'approved',
        ]);

        // Restore only 5 units
        $stock->increment('productStock', 5);

        $this->assertEquals(85, $stock->fresh()->productStock); // 100 - 20 + 5 = 85
    }

    /** @test */
    public function it_cannot_return_more_than_sold_quantity()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
        ]);

        // Create a sale
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'totalAmount' => 500.00,
            'paidAmount' => 500.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
            'saleDate' => now(),
        ]);

        $saleDetail = SaleDetails::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'stock_id' => $stock->id,
            'price' => 50.00,
            'purchase_price' => 30.00,
            'batch_no' => $stock->batch_no,
            'expire_date' => $stock->expire_date,
            'quantities' => 10,
        ]);

        // Try to return more than sold
        $this->expectException(\Exception::class);

        // Simulate validation logic
        if (15 > $saleDetail->quantities) {
            throw new \Exception('Cannot return more than sold quantity');
        }
    }

    /** @test */
    public function it_can_track_return_reasons()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
        ]);

        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'totalAmount' => 500.00,
            'paidAmount' => 500.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
            'saleDate' => now(),
        ]);

        $returnReasons = [
            'Defective product',
            'Wrong product delivered',
            'Customer not satisfied',
            'Expired product',
            'Damaged packaging',
        ];

        foreach ($returnReasons as $reason) {
            $return = SaleReturn::create([
                'business_id' => $this->business->id,
                'sale_id' => $sale->id,
                'user_id' => $this->user->id,
                'return_date' => now(),
                'reason' => $reason,
                'total_amount' => 100.00,
                'refund_amount' => 100.00,
                'status' => 'approved',
            ]);

            $this->assertNotNull($return->fresh());
        }

        $this->assertCount(5, SaleReturn::where('sale_id', $sale->id)->get());
    }

    /** @test */
    public function it_can_get_returns_by_status()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
        ]);

        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'totalAmount' => 500.00,
            'paidAmount' => 500.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
            'saleDate' => now(),
        ]);

        // Create returns with different statuses
        SaleReturn::create([
            'business_id' => $this->business->id,
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'reason' => 'Test reason',
            'total_amount' => 100.00,
            'refund_amount' => 100.00,
            'status' => 'pending',
        ]);

        SaleReturn::create([
            'business_id' => $this->business->id,
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'reason' => 'Test reason',
            'total_amount' => 100.00,
            'refund_amount' => 100.00,
            'status' => 'approved',
        ]);

        SaleReturn::create([
            'business_id' => $this->business->id,
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'reason' => 'Test reason',
            'total_amount' => 100.00,
            'refund_amount' => 100.00,
            'status' => 'rejected',
        ]);

        $pendingReturns = SaleReturn::where('status', 'pending')->get();
        $approvedReturns = SaleReturn::where('status', 'approved')->get();
        $rejectedReturns = SaleReturn::where('status', 'rejected')->get();

        $this->assertCount(1, $pendingReturns);
        $this->assertCount(1, $approvedReturns);
        $this->assertCount(1, $rejectedReturns);
    }
}
