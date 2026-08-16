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

        $saleService = new SaleService();
        
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

        $sale = $saleService->createSale($saleData, $business->id, $user->id);

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

        $saleService = new SaleService();
        
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

        $saleService->createSale($saleData, $business->id, $user->id);
    }

    public function test_invoice_service_creates_invoice()
    {
        $business = Business::factory()->create();
        $sale = Sale::factory()->create(['business_id' => $business->id]);

        $invoiceService = new \App\Services\InvoiceService();
        
        $invoiceData = [
            'business_id' => $business->id,
            'sale_id' => $sale->id,
            'invoice_number' => 'INV-TEST-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => $sale->totalAmount,
            'tax_amount' => 0,
            'currency' => 'SAR',
        ];

        $invoice = $invoiceService->createInvoice($invoiceData);

        $this->assertDatabaseHas('invoices', ['invoice_number' => 'INV-TEST-001']);
    }

    public function test_financial_transaction_service_creates_transaction()
    {
        $business = Business::factory()->create();

        $transactionService = new \App\Services\FinancialTransactionService();
        
        $transactionData = [
            'business_id' => $business->id,
            'type' => 'income',
            'amount' => 1000,
            'currency' => 'SAR',
            'reference_type' => 'manual',
            'reference_id' => null,
            'description' => 'Test transaction',
            'transaction_date' => now()->toDateString(),
        ];

        $transaction = $transactionService->createTransaction($transactionData);

        $this->assertDatabaseHas('financial_transactions', ['amount' => 1000]);
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

        $notificationService = new \App\Services\NotificationService();
        
        $notificationService->sendNotification(
            $business->id,
            'test_type',
            'Test Notification',
            'This is a test notification',
            $user->id
        );

        $this->assertDatabaseHas('notifications', ['type' => 'test_type']);
    }
}
