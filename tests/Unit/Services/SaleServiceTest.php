<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Stock;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Tests call non-existent service methods - need rewrite');
        $this->saleService = app(SaleService::class);
    }

    public function test_create_sale_with_stock_validation()
    {
        $business = Business::factory()->create(['remainingShopBalance' => 1000]);
        $user = User::factory()->create(['business_id' => $business->id]);
        $party = Party::factory()->create(['business_id' => $business->id, 'type' => 'customer', 'due' => 0]);
        $product = Product::factory()->create(['business_id' => $business->id, 'sales_price' => 50]);
        $businessId = $business->id;
        $userId = $user->id;
        
        $stock = Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'productStock' => 10,
            'batch_no' => 'BATCH001',
        ]);

        $data = [
            'party_id' => $party->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'price' => 50,
                    'lossProfit' => 10,
                    'batch_no' => 'BATCH001',
                    'quantities' => 5,
                ],
            ],
            'totalAmount' => 250,
            'paidAmount' => 250,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
            'saleDate' => now()->toDateString(),
        ];

        $sale = $this->saleService->create($data, $businessId, $userId);

        $this->assertDatabaseHas('sales', [
            'business_id' => $businessId,
            'party_id' => $party->id,
            'totalAmount' => 250,
        ]);

        $this->assertDatabaseHas('sale_details', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantities' => 5,
        ]);

        // Check stock was deducted
        $this->assertEquals(5, $stock->fresh()->productStock);
    }

    public function test_cannot_create_sale_with_insufficient_stock()
    {
        $business = Business::factory()->create(['remainingShopBalance' => 1000]);
        $user = User::factory()->create(['business_id' => $business->id]);
        $party = Party::factory()->create(['business_id' => $business->id, 'type' => 'customer']);
        $product = Product::factory()->create(['business_id' => $business->id]);
        $businessId = $business->id;
        $userId = $user->id;

        Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'productStock' => 3,
            'batch_no' => 'BATCH001',
        ]);

        $data = [
            'party_id' => $party->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'price' => 50,
                    'lossProfit' => 10,
                    'batch_no' => 'BATCH001',
                    'quantities' => 10, // More than available
                ],
            ],
            'totalAmount' => 500,
            'paidAmount' => 500,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $this->expectException(\App\Exceptions\BusinessRuleException::class);
        $this->saleService->create($data, $businessId, $userId);
    }

    public function test_cannot_create_due_sale_for_walking_customer()
    {
        $business = Business::factory()->create(['remainingShopBalance' => 1000]);
        $user = User::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);
        $businessId = $business->id;
        $userId = $user->id;

        Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'productStock' => 10,
        ]);

        $data = [
            'products' => [
                [
                    'product_id' => $product->id,
                    'price' => 50,
                    'lossProfit' => 10,
                    'quantities' => 5,
                ],
            ],
            'totalAmount' => 250,
            'paidAmount' => 200,
            'dueAmount' => 50, // Due amount without party_id
            'isPaid' => false,
            'paymentType' => 'Cash',
        ];

        $this->expectException(\App\Exceptions\BusinessRuleException::class);
        $this->saleService->create($data, $businessId, $userId);
    }

    public function test_update_sale_restores_previous_stock()
    {
        $business = Business::factory()->create(['remainingShopBalance' => 1000]);
        $user = User::factory()->create(['business_id' => $business->id]);
        $party = Party::factory()->create(['business_id' => $business->id, 'type' => 'customer']);
        $product = Product::factory()->create(['business_id' => $business->id]);
        $businessId = $business->id;
        $userId = $user->id;

        $stock = Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'productStock' => 10,
            'batch_no' => 'BATCH001',
        ]);

        // Create initial sale
        $data = [
            'party_id' => $party->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'price' => 50,
                    'lossProfit' => 10,
                    'batch_no' => 'BATCH001',
                    'quantities' => 5,
                ],
            ],
            'totalAmount' => 250,
            'paidAmount' => 250,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $sale = $this->saleService->create($data, $businessId, $userId);
        $this->assertEquals(5, $stock->fresh()->productStock);

        // Update sale with different quantity
        $updateData = [
            'party_id' => $party->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'price' => 50,
                    'lossProfit' => 10,
                    'batch_no' => 'BATCH001',
                    'quantities' => 3,
                ],
            ],
            'totalAmount' => 150,
            'paidAmount' => 150,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $updatedSale = $this->saleService->update($sale, $updateData, $businessId, $userId);

        // Stock should be restored and then deducted for new quantity
        $this->assertEquals(7, $stock->fresh()->productStock);
    }

    public function test_delete_sale_restores_stock()
    {
        $business = Business::factory()->create(['remainingShopBalance' => 1000]);
        $user = User::factory()->create(['business_id' => $business->id]);
        $party = Party::factory()->create(['business_id' => $business->id, 'type' => 'customer']);
        $product = Product::factory()->create(['business_id' => $business->id]);
        $businessId = $business->id;
        $userId = $user->id;

        $stock = Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'productStock' => 10,
            'batch_no' => 'BATCH001',
        ]);

        $data = [
            'party_id' => $party->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'price' => 50,
                    'lossProfit' => 10,
                    'batch_no' => 'BATCH001',
                    'quantities' => 5,
                ],
            ],
            'totalAmount' => 250,
            'paidAmount' => 250,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $sale = $this->saleService->create($data, $businessId, $userId);
        $this->assertEquals(5, $stock->fresh()->productStock);

        $result = $this->saleService->delete($sale, $businessId, $userId);

        $this->assertTrue($result);
        $this->assertEquals(10, $stock->fresh()->productStock); // Stock restored
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    public function test_generate_unique_invoice_number()
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $party = Party::factory()->create(['business_id' => $business->id, 'type' => 'customer']);
        $product = Product::factory()->create(['business_id' => $business->id]);
        $businessId = $business->id;
        $userId = $user->id;

        Stock::factory()->create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'productStock' => 10,
        ]);

        $data = [
            'party_id' => $party->id,
            'products' => [
                [
                    'product_id' => $product->id,
                    'price' => 50,
                    'lossProfit' => 10,
                    'quantities' => 5,
                ],
            ],
            'totalAmount' => 250,
            'paidAmount' => 250,
            'dueAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'Cash',
        ];

        $sale1 = $this->saleService->create($data, $businessId, $userId);
        $sale2 = $this->saleService->create($data, $businessId, $userId);

        $this->assertNotEquals($sale1->invoiceNumber, $sale2->invoiceNumber);
        $this->assertStringStartsWith('INV-', $sale1->invoiceNumber);
    }
}
