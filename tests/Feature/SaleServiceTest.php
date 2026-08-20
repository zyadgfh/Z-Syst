<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Stock;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected SaleService $saleService;
    protected Business $business;
    protected User $user;
    protected Party $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saleService = app(SaleService::class);
        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->customer = Party::factory()->create(['business_id' => $this->business->id]);
    }

    /** @test */
    public function it_can_create_a_sale_with_single_product()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
        ]);

        $saleData = [
            'party_id' => $this->customer->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 10,
                    'price' => 50.00,
                    'purchase_price' => 30.00,
                ],
            ],
            'totalAmount' => 500.00,
            'paidAmount' => 500.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $sale = $this->saleService->create($saleData, $this->business->id, $this->user->id);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'business_id' => $this->business->id,
            'party_id' => $this->customer->id,
            'totalAmount' => 500.00,
        ]);

        $this->assertDatabaseHas('sale_details', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantities' => 10,
        ]);

        // Verify stock was deducted
        $stock->refresh();
        $this->assertEquals(90, $stock->productStock);
    }

    /** @test */
    public function it_can_create_a_sale_with_multiple_products()
    {
        $product1 = Product::factory()->create(['business_id' => $this->business->id]);
        $product2 = Product::factory()->create(['business_id' => $this->business->id]);

        $stock1 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product1->id,
            'productStock' => 50,
        ]);

        $stock2 = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product2->id,
            'productStock' => 30,
        ]);

        $saleData = [
            'party_id' => $this->customer->id,
            'products' => [
                [
                    'product_id' => $product1->id,
                    'quantities' => 5,
                    'price' => 50.00,
                    'purchase_price' => 30.00,
                ],
                [
                    'product_id' => $product2->id,
                    'quantities' => 3,
                    'price' => 75.00,
                    'purchase_price' => 45.00,
                ],
            ],
            'totalAmount' => 475.00,
            'paidAmount' => 475.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $sale = $this->saleService->create($saleData, $this->business->id, $this->user->id);

        $this->assertCount(2, $sale->details);

        // Verify stock was deducted
        $stock1->refresh();
        $stock2->refresh();
        $this->assertEquals(45, $stock1->productStock);
        $this->assertEquals(27, $stock2->productStock);
    }

    /** @test */
    public function it_fails_to_create_sale_with_insufficient_stock()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 5,
        ]);

        $saleData = [
            'party_id' => $this->customer->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 10, // More than available
                    'price' => 50.00,
                    'purchase_price' => 30.00,
                ],
            ],
            'totalAmount' => 500.00,
            'paidAmount' => 500.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $this->expectException(\App\Exceptions\BusinessRuleException::class);

        $this->saleService->create($saleData, $this->business->id, $this->user->id);
    }

    /** @test */
    public function it_can_update_an_existing_sale()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
        ]);

        $saleData = [
            'party_id' => $this->customer->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 10,
                    'price' => 50.00,
                    'purchase_price' => 30.00,
                ],
            ],
            'totalAmount' => 500.00,
            'paidAmount' => 500.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $sale = $this->saleService->create($saleData, $this->business->id, $this->user->id);

        // Update sale
        $updateData = [
            'party_id' => $this->customer->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 15, // Increased quantity
                    'price' => 55.00,
                    'purchase_price' => 30.00,
                ],
            ],
            'totalAmount' => 825.00,
            'paidAmount' => 825.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $updatedSale = $this->saleService->update($sale, $updateData, $this->business->id, $this->user->id);

        $this->assertEquals(825.00, $updatedSale->totalAmount);

        // Verify stock was updated correctly (100 - 10 + 10 - 15 = 85)
        $stock->refresh();
        $this->assertEquals(85, $stock->productStock);
    }

    /** @test */
    public function it_can_delete_a_sale_and_restore_stock()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
        ]);

        $saleData = [
            'party_id' => $this->customer->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantities' => 10,
                    'price' => 50.00,
                    'purchase_price' => 30.00,
                ],
            ],
            'totalAmount' => 500.00,
            'paidAmount' => 500.00,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $sale = $this->saleService->create($saleData, $this->business->id, $this->user->id);

        $this->saleService->delete($sale, $this->business->id, $this->user->id);

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sale_details', ['sale_id' => $sale->id]);

        // Verify stock was restored
        $stock->refresh();
        $this->assertEquals(100, $stock->productStock);
    }
}
