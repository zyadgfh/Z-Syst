<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoicePayment;
use App\Models\User;
use App\Services\SupplierInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SupplierInvoiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected SupplierInvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = new SupplierInvoiceService;
    }

    /**
     * Test creating supplier invoice.
     */
    public function test_can_create_supplier_invoice()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $invoice = $this->invoiceService->create([
            'business_id' => $user->business_id,
            'supplier_id' => 1,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'tax_amount' => 100,
            'discount_amount' => 50,
            'items' => [
                [
                    'product_id' => $product->id,
                    'description' => 'Test Product',
                    'quantity' => 10,
                    'unit_price' => 100,
                    'discount' => 0,
                    'tax' => 10,
                ],
            ],
        ]);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => $invoice->id,
            'business_id' => $user->business_id,
            'invoice_number' => $invoice->invoice_number,
        ]);

        $this->assertEquals(SupplierInvoice::STATUS_PENDING, $invoice->status);
        $this->assertEquals(950, $invoice->total_amount); // (100 * 10) + 10 - 50
    }

    /**
     * Test creating invoice from purchase.
     */
    public function test_can_create_invoice_from_purchase()
    {
        $purchase = Purchase::factory()->create();
        $invoice = $this->invoiceService->createFromPurchase($purchase);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => $invoice->id,
            'purchase_id' => $purchase->id,
            'supplier_id' => $purchase->party_id,
        ]);

        $this->assertEquals(SupplierInvoice::STATUS_PENDING, $invoice->status);
    }

    /**
     * Test approving invoice.
     */
    public function test_can_approve_invoice()
    {
        $invoice = SupplierInvoice::factory()->create([
            'status' => SupplierInvoice::STATUS_PENDING,
        ]);

        $userId = User::factory()->create()->id;
        $invoice = $this->invoiceService->approve($invoice, $userId);

        $this->assertEquals(SupplierInvoice::STATUS_APPROVED, $invoice->status);
        $this->assertEquals($userId, $invoice->approved_by);
        $this->assertNotNull($invoice->approved_at);
    }

    /**
     * Test rejecting invoice.
     */
    public function test_can_reject_invoice()
    {
        $invoice = SupplierInvoice::factory()->create([
            'status' => SupplierInvoice::STATUS_PENDING,
        ]);

        $userId = User::factory()->create()->id;
        $invoice = $this->invoiceService->reject($invoice, $userId, 'Test rejection');

        $this->assertEquals(SupplierInvoice::STATUS_REJECTED, $invoice->status);
        $this->assertEquals($userId, $invoice->approved_by);
    }

    /**
     * Test cancelling invoice.
     */
    public function test_can_cancel_invoice()
    {
        $invoice = SupplierInvoice::factory()->create([
            'status' => SupplierInvoice::STATUS_APPROVED,
        ]);

        $invoice = $this->invoiceService->cancel($invoice, 'Test cancellation');

        $this->assertEquals(SupplierInvoice::STATUS_CANCELLED, $invoice->status);
        $this->assertNotNull($invoice->cancelled_at);
    }

    /**
     * Test adding payment to invoice.
     */
    public function test_can_add_payment_to_invoice()
    {
        $invoice = SupplierInvoice::factory()->create([
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance' => 1000,
            'status' => SupplierInvoice::STATUS_APPROVED,
        ]);

        $payment = $this->invoiceService->addPayment($invoice, [
            'payment_date' => now(),
            'payment_method' => 'cash',
            'amount' => 500,
        ]);

        $this->assertDatabaseHas('supplier_invoice_payments', [
            'id' => $payment->id,
            'supplier_invoice_id' => $invoice->id,
            'amount' => 500,
        ]);

        $invoice->refresh();
        $this->assertEquals(500, $invoice->paid_amount);
        $this->assertEquals(500, $invoice->balance);
        $this->assertEquals(SupplierInvoice::STATUS_PARTIALLY_PAID, $invoice->status);
    }

    /**
     * Test full payment marks invoice as paid.
     */
    public function test_full_payment_marks_invoice_as_paid()
    {
        $invoice = SupplierInvoice::factory()->create([
            'total_amount' => 1000,
            'paid_amount' => 500,
            'balance' => 500,
            'status' => SupplierInvoice::STATUS_PARTIALLY_PAID,
        ]);

        $payment = $this->invoiceService->addPayment($invoice, [
            'payment_date' => now(),
            'payment_method' => 'cash',
            'amount' => 500,
        ]);

        $invoice->refresh();
        $this->assertEquals(1000, $invoice->paid_amount);
        $this->assertEquals(0, $invoice->balance);
        $this->assertEquals(SupplierInvoice::STATUS_PAID, $invoice->status);
    }

    /**
     * Test approving payment.
     */
    public function test_can_approve_payment()
    {
        $payment = SupplierInvoicePayment::factory()->create([
            'status' => SupplierInvoicePayment::STATUS_PENDING,
        ]);

        $userId = User::factory()->create()->id;
        $payment = $this->invoiceService->approvePayment($payment, $userId);

        $this->assertEquals(SupplierInvoicePayment::STATUS_APPROVED, $payment->status);
        $this->assertEquals($userId, $payment->approved_by);
        $this->assertNotNull($payment->approved_at);
    }

    /**
     * Test invoice scopes.
     */
    public function test_invoice_scopes()
    {
        $businessId = 1;

        SupplierInvoice::factory()->create([
            'business_id' => $businessId,
            'status' => SupplierInvoice::STATUS_PENDING,
        ]);

        SupplierInvoice::factory()->create([
            'business_id' => $businessId,
            'status' => SupplierInvoice::STATUS_APPROVED,
            'due_date' => now()->subDays(10),
        ]);

        $pending = SupplierInvoice::forBusiness($businessId)->pending()->get();
        $overdue = SupplierInvoice::forBusiness($businessId)->overdue()->get();
        $unpaid = SupplierInvoice::forBusiness($businessId)->unpaid()->get();

        $this->assertCount(1, $pending);
        $this->assertCount(1, $overdue);
        $this->assertCount(2, $unpaid);
    }

    /**
     * Test invoice calculations.
     */
    public function test_invoice_calculations()
    {
        $invoice = SupplierInvoice::factory()->create([
            'total_amount' => 1000,
            'paid_amount' => 500,
            'balance' => 500,
        ]);

        $this->assertEquals(50, $invoice->getPaymentPercentage());
        $this->assertEquals(30, $invoice->getDaysUntilDue()); // Default 30 days
    }

    /**
     * Test invoice due soon check.
     */
    public function test_invoice_due_soon_check()
    {
        $invoice = SupplierInvoice::factory()->create([
            'due_date' => now()->addDays(5),
            'status' => SupplierInvoice::STATUS_APPROVED,
        ]);

        $this->assertTrue($invoice->isDueSoon());
    }

    /**
     * Test invoice critically overdue check.
     */
    public function test_invoice_critically_overdue_check()
    {
        $invoice = SupplierInvoice::factory()->create([
            'due_date' => now()->subDays(35),
            'status' => SupplierInvoice::STATUS_APPROVED,
        ]);

        $this->assertTrue($invoice->isCriticallyOverdue());
    }

    /**
     * Test invoice generation number.
     */
    public function test_invoice_number_generation()
    {
        $businessId = 1;

        $invoice1 = SupplierInvoice::factory()->create(['business_id' => $businessId]);
        $invoice2 = SupplierInvoice::factory()->create(['business_id' => $businessId]);

        $this->assertStringStartsWith('INV-', $invoice1->invoice_number);
        $this->assertStringStartsWith('INV-', $invoice2->invoice_number);
        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
    }

    /**
     * Test payment number generation.
     */
    public function test_payment_number_generation()
    {
        $businessId = 1;

        $payment1 = SupplierInvoicePayment::factory()->create(['business_id' => $businessId]);
        $payment2 = SupplierInvoicePayment::factory()->create(['business_id' => $businessId]);

        $this->assertStringStartsWith('PAY-', $payment1->payment_number);
        $this->assertStringStartsWith('PAY-', $payment2->payment_number);
        $this->assertNotEquals($payment1->payment_number, $payment2->payment_number);
    }

    /**
     * Test API endpoint - create invoice.
     */
    public function test_api_can_create_invoice()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/supplier-invoices', [
                'supplier_id' => 1,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'items' => [
                    [
                        'product_id' => $product->id,
                        'description' => 'Test Product',
                        'quantity' => 10,
                        'unit_price' => 100,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'invoice_number',
                    'status',
                ],
            ]);
    }

    /**
     * Test API endpoint - list invoices.
     */
    public function test_api_can_list_invoices()
    {
        $user = User::factory()->create();
        SupplierInvoice::factory()->count(3)->create(['business_id' => $user->business_id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/supplier-invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'invoice_number',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * Test API endpoint - approve invoice.
     */
    public function test_api_can_approve_invoice()
    {
        $user = User::factory()->create();
        $invoice = SupplierInvoice::factory()->create([
            'business_id' => $user->business_id,
            'status' => SupplierInvoice::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/supplier-invoices/{$invoice->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => SupplierInvoice::STATUS_APPROVED,
                ],
            ]);
    }

    /**
     * Test API endpoint - add payment.
     */
    public function test_api_can_add_payment()
    {
        $user = User::factory()->create();
        $invoice = SupplierInvoice::factory()->create([
            'business_id' => $user->business_id,
            'total_amount' => 1000,
            'status' => SupplierInvoice::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/supplier-invoices/{$invoice->id}/add-payment", [
                'payment_method' => 'cash',
                'amount' => 500,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'payment_number',
                    'amount',
                ],
            ]);
    }

    /**
     * Test API endpoint - statistics.
     */
    public function test_api_can_get_statistics()
    {
        $user = User::factory()->create();
        SupplierInvoice::factory()->count(5)->create(['business_id' => $user->business_id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/supplier-invoices/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'pending',
                    'overdue',
                    'unpaid',
                    'total_amount',
                    'paid_amount',
                    'balance',
                ],
            ]);
    }

    /**
     * Test API endpoint - aging report.
     */
    public function test_api_can_get_aging_report()
    {
        $user = User::factory()->create();
        SupplierInvoice::factory()->create([
            'business_id' => $user->business_id,
            'balance' => 1000,
            'due_date' => now()->subDays(10),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/supplier-invoices/aging-report');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period_30',
                    'period_60',
                    'period_90',
                    'period_90_plus',
                    'total',
                ],
            ]);
    }
}
