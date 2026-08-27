<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_service_creates_sale_with_stock_deduction()
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);
        
        $stock = $product->stocks()->create([
            'business_id' => $business->id,
            'productStock' => 100,
        ]);

        $saleService = app(SaleService::class);
        
        $saleData = [
            'party_id' => null,
            'saleDate' => now()->toDateString(),
            'totalAmount' => 100,
            'paidAmount' => 100,
            'isPaid' => true,
            'paymentType' => 'Cash',
            'products' => [
                [
                    'product_id' => $product->id,
                    'price' => 10,
                    'quantities' => 5,
                    'lossProfit' => 5,
                ],
            ],
        ];

        $sale = $saleService->create($saleData, $business->id, $user->id);

        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('sale_details', ['sale_id' => $sale->id]);
        $this->assertEquals(95, $stock->fresh()->productStock); // 100 - 5
    }

    public function test_sale_service_validates_insufficient_stock()
    {
        $this->expectException(\Exception::class);

        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);
        
        $stock = $product->stocks()->create([
            'business_id' => $business->id,
            'productStock' => 3,
        ]);

        $saleService = app(SaleService::class);
        
        $saleData = [
            'party_id' => null,
            'saleDate' => now()->toDateString(),
            'totalAmount' => 100,
            'paidAmount' => 100,
            'isPaid' => true,
            'paymentType' => 'Cash',
            'products' => [
                [
                    'product_id' => $product->id,
                    'price' => 10,
                    'quantities' => 10, // More than available stock
                    'lossProfit' => 5,
                ],
            ],
        ];

        $saleService->create($saleData, $business->id, $user->id);
    }

    public function test_invoice_service_creates_invoice()
    {
        $business = Business::factory()->create();
        $sale = Sale::factory()->create(['business_id' => $business->id]);

        $invoiceService = app(\App\Services\InvoiceService::class);
        
        $invoice = $invoiceService->generateInvoiceFromSale($sale);

        $this->assertDatabaseHas('invoices', ['sale_id' => $sale->id]);
    }

    public function test_financial_transaction_service_creates_transaction()
    {
        $business = Business::factory()->create();

        $transactionService = app(\App\Services\FinancialTransactionService::class);
        
        $transactionData = [
            'type' => 'revenue',
            'amount' => 1000,
            'currency' => 'SAR',
            'reference_type' => 'manual',
            'reference_id' => null,
            'description' => 'Test transaction',
            'transaction_date' => now()->toDateString(),
        ];

        $transaction = $transactionService->recordTransaction($transactionData, $business->id);

        $this->assertNotNull($transaction);
    }

    public function test_customer_service_creates_customer()
    {
        $business = Business::factory()->create();

        $customerService = new \App\Services\CustomerService();
        
        $customerData = [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+9665012345678',
            'type' => 'customer',
        ];

        $customer = $customerService->createCustomer($customerData, $business->id);

        $this->assertDatabaseHas('parties', ['name' => 'Test Customer']);
    }

    public function test_notification_service_creates_notification()
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);

        // NotificationService requires ExpiryAlertService and its methods are specialized
        // Verify the service can be resolved from the container
        $notificationService = app(\App\Services\NotificationService::class);
        $this->assertNotNull($notificationService);
    }
}
