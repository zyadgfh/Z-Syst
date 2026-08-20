<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseStockIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseService $purchaseService;
    private Business $business;
    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseService = app(PurchaseService::class);
        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);
    }

    public function test_purchase_increases_stock_correctly()
    {
        $party = Party::factory()->create(['business_id' => $this->business->id]);

        $purchaseData = [
            'party_id' => $party->id,
            'paymentType' => 'cash',
            'purchaseDate' => now()->toDateString(),
            'totalAmount' => 1000,
            'paidAmount' => 1000,
            'dueAmount' => 0,
            'discountAmount' => 0,
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'batch_no' => 'NEW-BATCH-002',
                    'expire_date' => now()->addYears(2)->toDateString(),
                    'quantities' => 50,
                    'sales_price' => 40,
                    'profit_percent' => 20,
                    'wholesale_price' => 35,
                    'purchase_with_tax' => 20,
                    'purchase_without_tax' => 20,
                ]
            ]
        ];

        $purchase = $this->purchaseService->create($purchaseData, $this->business->id, $this->user->id);

        $this->assertNotNull($purchase);
        $this->assertEquals(1000, $purchase->totalAmount);

        // Verify stock was created with correct quantity
        $stock = Stock::where('product_id', $this->product->id)->where('batch_no', 'NEW-BATCH-002')->first();
        $this->assertNotNull($stock);
        $this->assertEquals(50, $stock->productStock);

        // Verify StockMovement was logged
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $stock->id,
            'movement_type' => 'in',
            'quantity' => 50,
            'reference_type' => \App\Models\Purchase::class,
            'reference_id' => $purchase->id,
        ]);

        // Verify FinancialTransaction was logged
        $this->assertDatabaseHas('financial_transactions', [
            'business_id' => $this->business->id,
            'type' => 'expense',
            'amount' => 1000,
            'reference_type' => 'purchase',
            'reference_id' => $purchase->id,
            'category' => 'purchases',
        ]);
    }
}
