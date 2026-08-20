<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleStockIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $saleService;
    private Business $business;
    private User $user;
    private Product $product;
    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saleService = app(SaleService::class);
        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);
        
        $this->stock = Stock::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'batch_no' => 'BATCH-001',
            'expire_date' => now()->addYear(),
            'productStock' => 100,
        ]);
    }

    public function test_sale_decreases_stock_correctly()
    {
        $party = Party::factory()->create(['business_id' => $this->business->id]);

        $saleData = [
            'party_id' => $party->id,
            'paymentType' => 'cash',
            'saleDate' => now()->toDateString(),
            'totalAmount' => 500,
            'paidAmount' => 500,
            'dueAmount' => 0,
            'discountAmount' => 0,
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'batch_no' => 'BATCH-001',
                    'quantities' => 10,
                    'price' => 50,
                    'purchase_price' => 30,
                    'lossProfit' => 200,
                ]
            ]
        ];

        $sale = $this->saleService->create($saleData, $this->business->id, $this->user->id);

        $this->assertNotNull($sale);
        $this->assertEquals(500, $sale->totalAmount);

        // Verify stock decreased from 100 to 90
        $this->stock->refresh();
        $this->assertEquals(90, $this->stock->productStock);

        // Verify StockMovement was logged
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $this->stock->id,
            'movement_type' => 'out',
            'quantity' => 10,
            'reference_type' => \App\Models\Sale::class,
            'reference_id' => $sale->id,
        ]);

        // Verify FinancialTransaction was logged
        $this->assertDatabaseHas('financial_transactions', [
            'business_id' => $this->business->id,
            'type' => 'revenue',
            'amount' => 500,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'category' => 'sales',
        ]);
    }

    public function test_sale_fails_if_insufficient_stock()
    {
        $party = Party::factory()->create(['business_id' => $this->business->id]);

        $saleData = [
            'party_id' => $party->id,
            'paymentType' => 'cash',
            'saleDate' => now()->toDateString(),
            'totalAmount' => 6000,
            'paidAmount' => 6000,
            'dueAmount' => 0,
            'discountAmount' => 0,
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'batch_no' => 'BATCH-001',
                    'quantities' => 150, // More than the available 100
                    'price' => 40,
                    'purchase_price' => 30,
                    'lossProfit' => 1500,
                ]
            ]
        ];

        $this->expectException(\App\Exceptions\BusinessRuleException::class);

        $this->saleService->create($saleData, $this->business->id, $this->user->id);

        // Verify stock remained unchanged
        $this->stock->refresh();
        $this->assertEquals(100, $this->stock->productStock);
    }
}
