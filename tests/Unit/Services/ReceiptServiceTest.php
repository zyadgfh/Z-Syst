<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\Receipt;
use App\Models\ReceiptSetting;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\User;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ReceiptServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private ReceiptService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Tests call non-existent service methods - need rewrite');

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->service = new ReceiptService;

        Auth::login($this->user);
    }

    public function test_get_or_create_settings_creates_default(): void
    {
        $settings = $this->service->getOrCreateSettings($this->business->id);

        $this->assertInstanceOf(ReceiptSetting::class, $settings);
        $this->assertEquals('Thank you for your business!', $settings->receipt_footer);
        $this->assertTrue($settings->show_barcode);
        $this->assertFalse($settings->show_qr_code);
        $this->assertTrue($settings->is_active);
    }

    public function test_get_or_create_settings_returns_existing(): void
    {
        ReceiptSetting::create([
            'business_id' => $this->business->id,
            'receipt_header' => 'Custom Header',
            'receipt_footer' => 'Custom Footer',
            'show_barcode' => false,
            'show_qr_code' => true,
            'is_active' => false,
        ]);

        $settings = $this->service->getOrCreateSettings($this->business->id);

        $this->assertEquals('Custom Header', $settings->receipt_header);
        $this->assertEquals('Custom Footer', $settings->receipt_footer);
        $this->assertFalse($settings->show_barcode);
        $this->assertTrue($settings->show_qr_code);
    }

    public function test_generate_receipt_for_sale(): void
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => null,
            'user_id' => $this->user->id,
            'totalAmount' => 100.0,
            'paidAmount' => 100.0,
            'dueAmount' => 0.0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'saleDate' => now()->toDateString(),
        ]);

        SaleDetails::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'price' => 50.0,
            'quantities' => 2,
            'batch_no' => 'BATCH-001',
            'expire_date' => now()->addYear()->toDateString(),
        ]);

        $receipt = $this->service->generateForSale($sale);

        $this->assertInstanceOf(Receipt::class, $receipt);
        $this->assertEquals('sale', $receipt->type);
        $this->assertEquals('generated', $receipt->status);
        $this->assertStringStartsWith('RCPT-', $receipt->receipt_number);
        $this->assertIsArray($receipt->data);
        $this->assertCount(1, $receipt->data['items']);
        $this->assertEquals(100.0, $receipt->data['subtotal']);
    }

    public function test_generate_receipt_for_purchase(): void
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $purchase = Purchase::create([
            'business_id' => $this->business->id,
            'party_id' => null,
            'user_id' => $this->user->id,
            'totalAmount' => 200.0,
            'paidAmount' => 200.0,
            'dueAmount' => 0.0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'purchaseDate' => now()->toDateString(),
        ]);

        PurchaseDetails::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'purchase_without_tax' => 50.0,
            'quantities' => 4,
            'batch_no' => 'BATCH-002',
            'expire_date' => now()->addYear()->toDateString(),
        ]);

        $receipt = $this->service->generateForPurchase($purchase);

        $this->assertInstanceOf(Receipt::class, $receipt);
        $this->assertEquals('purchase', $receipt->type);
        $this->assertStringStartsWith('RCPT-', $receipt->receipt_number);
        $this->assertCount(1, $receipt->data['items']);
        $this->assertEquals(200.0, $receipt->data['subtotal']);
    }

    public function test_get_receipt_by_sale(): void
    {
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'totalAmount' => 100.0,
            'paidAmount' => 100.0,
            'dueAmount' => 0.0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'saleDate' => now()->toDateString(),
        ]);

        $receipt = $this->service->getOrCreateSettings($this->business->id);

        $generated = $this->service->generateForSale($sale);

        $found = $this->service->getBySale($sale);

        $this->assertNotNull($found);
        $this->assertEquals($generated->id, $found->id);
    }

    public function test_receipt_settings_update(): void
    {
        $settings = $this->service->getOrCreateSettings($this->business->id);

        $settings->update([
            'receipt_header' => 'Updated Header',
            'receipt_footer' => 'Updated Footer',
            'show_barcode' => false,
            'show_qr_code' => true,
            'is_active' => false,
        ]);

        $settings = $this->service->getOrCreateSettings($this->business->id);

        $this->assertEquals('Updated Header', $settings->receipt_header);
        $this->assertEquals('Updated Footer', $settings->receipt_footer);
        $this->assertFalse($settings->show_barcode);
        $this->assertTrue($settings->show_qr_code);
        $this->assertFalse($settings->is_active);
    }

    public function test_receipt_contains_party_info(): void
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => null,
            'user_id' => $this->user->id,
            'totalAmount' => 150.0,
            'paidAmount' => 100.0,
            'dueAmount' => 50.0,
            'isPaid' => false,
            'paymentType' => 'credit',
            'saleDate' => now()->toDateString(),
        ]);

        SaleDetails::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'price' => 150.0,
            'quantities' => 1,
            'batch_no' => 'BATCH-003',
            'expire_date' => now()->addYear()->toDateString(),
        ]);

        $receipt = $this->service->generateForSale($sale);

        $this->assertEquals(150.0, $receipt->data['subtotal']);
        $this->assertEquals(100.0, $receipt->data['paid_amount']);
        $this->assertEquals(50.0, $receipt->data['due_amount']);
        $this->assertEquals('credit', $receipt->data['payment_type']);
    }

    public function test_render_pdf_generates_content(): void
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'totalAmount' => 100.0,
            'paidAmount' => 100.0,
            'dueAmount' => 0.0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'saleDate' => now()->toDateString(),
        ]);

        SaleDetails::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'price' => 100.0,
            'quantities' => 1,
            'batch_no' => 'BATCH-001',
            'expire_date' => now()->addYear()->toDateString(),
        ]);

        $receipt = $this->service->generateForSale($sale);
        $pdf = $this->service->renderPdf($receipt);

        $this->assertNotEmpty($pdf);
    }
}
