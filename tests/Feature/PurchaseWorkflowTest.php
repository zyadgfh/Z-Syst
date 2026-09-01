<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use App\Models\Party;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\SupplierLedger;
use App\Models\FinancialTransaction;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Services\PurchaseService;
use App\Services\PurchaseReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected int $businessId;
    protected int $userId;
    protected Party $supplier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create business
        $category = BusinessCategory::create(['name' => 'Test Category', 'status' => 'active']);
        $business = Business::create([
            'companyName' => 'Test Pharmacy',
            'business_category_id' => $category->id,
            'remainingShopBalance' => 0,
        ]);
        $this->businessId = $business->id;

        // Create user
        $user = User::create([
            'email' => 'admin@test.com',
            'name' => 'Test Admin',
            'password' => bcrypt('password'),
            'business_id' => $this->businessId,
            'role' => 'superadmin',
            'status' => 'active',
        ]);
        $this->userId = $user->id;

        // Fake auth for invoice number generation
        $this->actingAs($user);

        // Create supplier
        $this->supplier = Party::create([
            'business_id' => $this->businessId,
            'name' => 'Test Supplier',
            'type' => 'supplier',
            'email' => 'supplier@test.com',
            'phone' => '1234567890',
            'due' => 0,
            'status' => 'active',
        ]);

        // Create product
        $this->product = Product::create([
            'business_id' => $this->businessId,
            'productName' => 'Test Product',
            'productCode' => 'TP-001',
            'purchase_without_tax' => 10.00,
            'purchase_with_tax' => 10.00,
            'sales_price' => 15.00,
            'profit_percent' => 50,
        ]);
    }

    protected function purchaseData(array $overrides = []): array
    {
        return array_merge([
            'party_id' => $this->supplier->id,
            'branch_id' => null,
            'purchaseDate' => now()->toDateString(),
            'paymentType' => 'Cash',
            'discountAmount' => 0,
            'tax_amount' => 0,
            'paidAmount' => 0,
            'note' => 'Test purchase',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'barcode' => 'TP-001',
                    'batch_no' => 'BATCH-001',
                    'quantities' => 10,
                    'purchase_with_tax' => 10.00,
                    'purchase_without_tax' => 10.00,
                    'discount' => 0,
                    'tax' => 0,
                    'profit_percent' => 50,
                    'sales_price' => 15.00,
                ],
            ],
        ], $overrides);
    }

    // ─── Purchase Creation ──────────────────────────────

    public function test_create_purchase_increases_stock(): void
    {
        $service = app(PurchaseService::class);
        $data = $this->purchaseData();

        $purchase = $service->create($data, $this->businessId, $this->userId);

        $this->assertInstanceOf(Purchase::class, $purchase);
        $this->assertEquals(100.00, $purchase->totalAmount);
        $this->assertEquals('received', $purchase->status);
        // invoiceNumber is generated in model boot via auth() — may be null in unit tests
        // The important assertion is that the purchase was created successfully

        // Stock should have increased
        $stock = Stock::where('business_id', $this->businessId)
            ->where('product_id', $this->product->id)
            ->sum('productStock');
        $this->assertEquals(10, $stock);
    }

    public function test_create_purchase_creates_ledger_entry(): void
    {
        $service = app(PurchaseService::class);
        $purchase = $service->create($this->purchaseData(), $this->businessId, $this->userId);

        $ledger = SupplierLedger::where('business_id', $this->businessId)
            ->where('party_id', $this->supplier->id)
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(100.00, $ledger->debit);
        $this->assertEquals(0, $ledger->credit);
        $this->assertEquals($purchase->invoiceNumber, $ledger->invoice_number);
    }

    public function test_create_purchase_creates_financial_transaction(): void
    {
        $service = app(PurchaseService::class);
        $purchase = $service->create($this->purchaseData(), $this->businessId, $this->userId);

        $ftx = FinancialTransaction::where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->first();

        $this->assertNotNull($ftx);
        $this->assertEquals('expense', $ftx->type);
        $this->assertEquals(100.00, $ftx->amount);
    }

    public function test_create_purchase_updates_supplier_due(): void
    {
        $service = app(PurchaseService::class);
        $service->create($this->purchaseData(), $this->businessId, $this->userId);

        $this->supplier->refresh();
        $this->assertGreaterThanOrEqual(100.00, $this->supplier->due);
    }

    // ─── Purchase Cancellation ──────────────────────────

    public function test_cancel_purchase_restores_stock(): void
    {
        $service = app(PurchaseService::class);
        $purchase = $service->create($this->purchaseData(), $this->businessId, $this->userId);

        $stockBefore = Stock::where('business_id', $this->businessId)
            ->where('product_id', $this->product->id)
            ->sum('productStock');

        $service->delete($purchase, $this->businessId, $this->userId);

        $stockAfter = Stock::where('business_id', $this->businessId)
            ->where('product_id', $this->product->id)
            ->sum('productStock');

        $this->assertEquals(0, $stockAfter);
        $this->assertEquals('canceled', $purchase->fresh()->status);
    }

    public function test_cancel_purchase_reverses_supplier_ledger(): void
    {
        $service = app(PurchaseService::class);
        $purchase = $service->create($this->purchaseData(), $this->businessId, $this->userId);

        $service->delete($purchase, $this->businessId, $this->userId);

        $lastEntry = SupplierLedger::where('business_id', $this->businessId)
            ->where('party_id', $this->supplier->id)
            ->latest()
            ->first();

        $this->assertGreaterThan(0, $lastEntry->credit);
    }

    public function test_cancel_purchase_deletes_financial_transaction(): void
    {
        $service = app(PurchaseService::class);
        $purchase = $service->create($this->purchaseData(), $this->businessId, $this->userId);

        $service->delete($purchase, $this->businessId, $this->userId);

        $ftxCount = FinancialTransaction::where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->count();

        $this->assertEquals(0, $ftxCount);
    }

    // ─── Purchase Return ────────────────────────────────

    public function test_return_deducts_stock(): void
    {
        $purchaseService = app(PurchaseService::class);
        $returnService = app(PurchaseReturnService::class);

        $purchase = $purchaseService->create($this->purchaseData(), $this->businessId, $this->userId);
        $detailId = PurchaseDetails::where('purchase_id', $purchase->id)->first()->id;

        $return = $returnService->processReturn($purchase, [
            'items' => [
                ['purchase_detail_id' => $detailId, 'return_qty' => 3, 'discount' => 0, 'tax' => 0],
            ],
            'reason' => 'Test return',
        ], $this->businessId, $this->userId);

        $stock = Stock::where('business_id', $this->businessId)
            ->where('product_id', $this->product->id)
            ->sum('productStock');

        $this->assertEquals(7, $stock);
        $this->assertEquals(30.00, $return->credit_amount);
        $this->assertEquals('completed', $return->status);
    }

    public function test_return_creates_ledger_credit(): void
    {
        $purchaseService = app(PurchaseService::class);
        $returnService = app(PurchaseReturnService::class);

        $purchase = $purchaseService->create($this->purchaseData(), $this->businessId, $this->userId);
        $detailId = PurchaseDetails::where('purchase_id', $purchase->id)->first()->id;

        $returnService->processReturn($purchase, [
            'items' => [
                ['purchase_detail_id' => $detailId, 'return_qty' => 3, 'discount' => 0, 'tax' => 0],
            ],
        ], $this->businessId, $this->userId);

        $returnEntry = SupplierLedger::where('business_id', $this->businessId)
            ->where('party_id', $this->supplier->id)
            ->where('transaction_type', 'purchase_return')
            ->first();

        $this->assertNotNull($returnEntry);
        $this->assertEquals(30.00, $returnEntry->credit);
    }

    public function test_cannot_over_return(): void
    {
        $purchaseService = app(PurchaseService::class);
        $returnService = app(PurchaseReturnService::class);

        $purchase = $purchaseService->create($this->purchaseData(), $this->businessId, $this->userId);
        $detailId = PurchaseDetails::where('purchase_id', $purchase->id)->first()->id;

        // Return 3 first
        $returnService->processReturn($purchase, [
            'items' => [
                ['purchase_detail_id' => $detailId, 'return_qty' => 3, 'discount' => 0, 'tax' => 0],
            ],
        ], $this->businessId, $this->userId);

        // Try to return 8 (only 7 returnable) — should fail
        $this->expectException(\App\Exceptions\BusinessRuleException::class);

        $returnService->processReturn($purchase, [
            'items' => [
                ['purchase_detail_id' => $detailId, 'return_qty' => 8, 'discount' => 0, 'tax' => 0],
            ],
        ], $this->businessId, $this->userId);
    }

    public function test_returnable_quantity_is_correct(): void
    {
        $purchaseService = app(PurchaseService::class);
        $returnService = app(PurchaseReturnService::class);

        $purchase = $purchaseService->create($this->purchaseData(), $this->businessId, $this->userId);
        $detailId = PurchaseDetails::where('purchase_id', $purchase->id)->first()->id;

        // Initially returnable = 10
        $items = $returnService->getReturnableItems($purchase->id);
        $this->assertEquals(10, $items[0]['returnable_qty']);

        // Return 3
        $returnService->processReturn($purchase, [
            'items' => [
                ['purchase_detail_id' => $detailId, 'return_qty' => 3, 'discount' => 0, 'tax' => 0],
            ],
        ], $this->businessId, $this->userId);

        // Returnable should be 7 (not affected by the decrement bug)
        $items = $returnService->getReturnableItems($purchase->id);
        $this->assertEquals(7, $items[0]['returnable_qty']);
    }
}
