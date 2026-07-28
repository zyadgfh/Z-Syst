<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use App\Modules\Sales\Application\Services\SaleService;
use App\Modules\Sales\Domain\DTOs\CreateSaleDTO;
use App\Services\FefoStockService;
use App\Services\StockBatchService;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * اختبارات تدفق وحدة المبيعات
 *
 * تغطي هذه الاختبارات:
 * 1. إنشاء فاتورة كاملة مع FEFO
 * 2. إلغاء فاتورة مع استعادة المخزون (FEFO Restore)
 * 3. مرتجع مبيعات مع استعادة المخزون
 * 4. الدفع بالمحفظة والمدفوعات المتعددة
 *
 * Z-Syst Pharmacy Sales Module Integration Tests
 */
class SalesModuleFlowTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;
    private Branch $branch;
    private User $user;
    private SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        // Bind company ID for tenant resolution
        app()->instance('tenant.company_id', $this->company->id);

        // Resolve SaleService from container with real dependencies
        $this->saleService = App::make(SaleService::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Helper Methods
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * إنشاء منتج مع مخزون (FEFO ready)
     */
    private function createProductWithStock(
        string $name = 'Test Medicine',
        float $price = 50.00,
        float $costPrice = 30.00,
        float $quantity = 100.0,
        ?string $batchNumber = null,
        ?string $expiryDate = null,
        bool $trackInventory = true
    ): array {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => $name,
            'generic_name' => $name,
            'barcode' => 'BARCODE-' . uniqid(),
            'sku' => 'SKU-' . uniqid(),
            'sales_price' => $price,
            'selling_price' => $price,
            'cost_price' => $costPrice,
            'track_inventory' => $trackInventory,
            'is_active' => true,
        ]);

        $inventory = null;
        if ($trackInventory && $quantity > 0) {
            $inventory = Inventory::create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'product_id' => $product->id,
                'batch_number' => $batchNumber ?? 'BATCH-' . uniqid(),
                'expiry_date' => $expiryDate ?? now()->addYear(),
                'quantity' => $quantity,
                'reserved_quantity' => 0,
                'cost_price' => $costPrice,
                'selling_price' => $price,
                'status' => 'available',
                'received_at' => now(),
                'last_moved_at' => now(),
            ]);
        }

        return [
            'product' => $product,
            'inventory' => $inventory,
        ];
    }

    /**
     * إنشاء DTO لفاتورة جديدة
     */
    private function createSaleDTO(array $itemsData, array $extra = []): CreateSaleDTO
    {
        $items = [];
        foreach ($itemsData as $item) {
            $items[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'] ?? 0,
                'tax' => $item['tax'] ?? 0,
            ];
        }

        return new CreateSaleDTO(
            items: $items,
            customer_id: $extra['customer_id'] ?? null,
            customer_name: $extra['customer_name'] ?? 'نقدي',
            customer_phone: $extra['customer_phone'] ?? null,
            branch_id: $extra['branch_id'] ?? $this->branch->id,
            company_id: $extra['company_id'] ?? $this->company->id,
            payment_method: $extra['payment_method'] ?? 'cash',
            amount_paid: $extra['amount_paid'] ?? null,
            discount_amount: $extra['discount_amount'] ?? 0,
            tax_amount: $extra['tax_amount'] ?? 0,
            tax_rate: $extra['tax_rate'] ?? 0,
            notes: $extra['notes'] ?? null,
            created_by: $extra['created_by'] ?? $this->user->id,
            prescription_id: $extra['prescription_id'] ?? null,
            payments: $extra['payments'] ?? null,
            cash_register_id: $extra['cash_register_id'] ?? null,
            insurance_claim_id: $extra['insurance_claim_id'] ?? null,
            wallet_data: $extra['wallet_data'] ?? null,
        );
    }

    /**
     * التحقق من المخزون المتبقي لمنتج في فرع
     */
    private function assertInventoryQuantity(string $productId, float $expectedQuantity): void
    {
        $totalQty = Inventory::where('product_id', $productId)
            ->where('branch_id', $this->branch->id)
            ->sum('quantity');

        $this->assertEquals($expectedQuantity, (float) $totalQty,
            "Expected inventory quantity {$expectedQuantity} but got {$totalQty}");
    }

    /**
     * التحقق من وجود حركة مخزون لنوع مرجعي معين
     */
    private function assertStockMovementExists(string $referenceType, string $referenceId, string $movementType): void
    {
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'movement_type' => $movementType,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  1. اختبار إنشاء فاتورة
    // ══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_creates_a_sale_with_fefo_stock_deduction(): void
    {
        // ── Arrange ──
        $productData = $this->createProductWithStock(
            name: 'Panadol Extra',
            price: 25.00,
            costPrice: 15.00,
            quantity: 50.0
        );
        $product = $productData['product'];

        $dto = $this->createSaleDTO([
            [
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_price' => 25.00,
            ],
        ], [
            'amount_paid' => 125.00,
        ]);

        // ── Act ──
        $sale = $this->saleService->create($dto);

        // ── Assert ──
        // 1. تم إنشاء الفاتورة
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(125.00, (float) $sale->total_amount);
        $this->assertEquals(125.00, (float) $sale->amount_paid);
        $this->assertEquals(0, (float) $sale->change_amount);

        // 2. تم إنشاء بند الفاتورة
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 25.00,
        ]);

        // 3. تم خصم المخزون (50 - 5 = 45)
        $this->assertInventoryQuantity($product->id, 45.0);

        // 4. تم تسجيل حركة مخزون (out)
        $this->assertStockMovementExists('sale', $sale->id, 'out');
    }

    /** @test */
    public function it_creates_a_sale_with_multiple_products(): void
    {
        // ── Arrange ──
        $product1 = $this->createProductWithStock('Product A', 100, 60, 30);
        $product2 = $this->createProductWithStock('Product B', 50, 30, 20);

        $dto = $this->createSaleDTO([
            [
                'product_id' => $product1['product']->id,
                'quantity' => 3,
                'unit_price' => 100.00,
            ],
            [
                'product_id' => $product2['product']->id,
                'quantity' => 2,
                'unit_price' => 50.00,
            ],
        ], [
            'amount_paid' => 400.00,
        ]);

        // ── Act ──
        $sale = $this->saleService->create($dto);

        // ── Assert ──
        $this->assertEquals(400.00, (float) $sale->total_amount);

        // تم خصم المخزون لكلا المنتجين
        $this->assertInventoryQuantity($product1['product']->id, 27.0); // 30 - 3
        $this->assertInventoryQuantity($product2['product']->id, 18.0); // 20 - 2

        $this->assertEquals(2, $sale->items()->count());
    }

    /** @test */
    public function it_fails_when_insufficient_stock(): void
    {
        // ── Arrange ──
        $productData = $this->createProductWithStock(
            name: 'Limited Stock Item',
            price: 100,
            costPrice: 60,
            quantity: 5.0  // Only 5 in stock
        );

        $dto = $this->createSaleDTO([
            [
                'product_id' => $productData['product']->id,
                'quantity' => 10,  // Trying to sell 10
                'unit_price' => 100.00,
            ],
        ], [
            'amount_paid' => 1000.00,
        ]);

        // ── Assert ──
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient stock');

        // ── Act ──
        $this->saleService->create($dto);
    }

    /** @test */
    public function it_creates_partial_sale_when_paid_less_than_total(): void
    {
        // ── Arrange ──
        $productData = $this->createProductWithStock(
            name: 'Partial Payment Item',
            price: 200,
            costPrice: 120,
            quantity: 10
        );

        $dto = $this->createSaleDTO([
            [
                'product_id' => $productData['product']->id,
                'quantity' => 2,
                'unit_price' => 200.00,
            ],
        ], [
            'amount_paid' => 200.00,  // Total is 400, paid 200
        ]);

        // ── Act ──
        $sale = $this->saleService->create($dto);

        // ── Assert ──
        $this->assertEquals('partial', $sale->status);
        $this->assertEquals(400.00, (float) $sale->total_amount);
        $this->assertEquals(200.00, (float) $sale->amount_paid);
        $this->assertEquals(8, (float) Inventory::where('product_id', $productData['product']->id)
            ->where('branch_id', $this->branch->id)
            ->sum('quantity'));  // 10 - 2 = 8
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  2. اختبار إلغاء فاتورة مع FEFO Restore
    // ══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_voids_a_sale_and_restores_stock(): void
    {
        // ── Arrange ──
        $productData = $this->createProductWithStock(
            name: 'Void Test Medicine',
            price: 75.00,
            costPrice: 45.00,
            quantity: 30.0
        );
        $product = $productData['product'];

        // إنشاء فاتورة
        $dto = $this->createSaleDTO([
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_price' => 75.00,
            ],
        ], [
            'amount_paid' => 750.00,
        ]);

        $sale = $this->saleService->create($dto);

        // التأكد من خصم المخزون
        $this->assertInventoryQuantity($product->id, 20.0); // 30 - 10

        // ── Act: إلغاء الفاتورة ──
        $voidedSale = $this->saleService->voidSale(
            $sale->id,
            'خطأ في الفاتورة'
        );

        // ── Assert ──
        // 1. حالة الفاتورة 'voided'
        $this->assertEquals('voided', $voidedSale->status);
        $this->assertNotNull($voidedSale->voided_at);
        $this->assertEquals('خطأ في الفاتورة', $voidedSale->void_reason);

        // 2. تم استعادة المخزون (20 + 10 = 30)
        $this->assertInventoryQuantity($product->id, 30.0);

        // 3. تم تسجيل حركة استعادة المخزون
        $this->assertStockMovementExists('sale_void', $sale->id, 'in');
    }

    /** @test */
    public function it_fails_to_void_an_already_voided_sale(): void
    {
        // ── Arrange ──
        $productData = $this->createProductWithStock(
            name: 'Double Void Test',
            price: 50,
            costPrice: 30,
            quantity: 20
        );

        $dto = $this->createSaleDTO([
            [
                'product_id' => $productData['product']->id,
                'quantity' => 5,
                'unit_price' => 50.00,
            ],
        ], [
            'amount_paid' => 250.00,
        ]);

        $sale = $this->saleService->create($dto);

        // إلغاء الفاتورة أول مرة
        $this->saleService->voidSale($sale->id, 'إلغاء أول');

        // ── Assert ──
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already voided');

        // ── Act: محاولة إلغاء مرة ثانية ──
        $this->saleService->voidSale($sale->id, 'إلغاء ثاني');
    }

    /** @test */
    public function it_restores_correct_stock_from_specific_batch_on_void(): void
    {
        // ── Arrange ──
        // إنشاء منتج بدفعتين مختلفتين
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Multi-Batch Item',
            'sales_price' => 100,
            'selling_price' => 100,
            'cost_price' => 60,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        // دفعة أولى (تاريخ انتهاء أبكر - تخرج أولاً في FEFO)
        Inventory::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-A',
            'expiry_date' => now()->addMonth(),
            'quantity' => 20,
            'reserved_quantity' => 0,
            'cost_price' => 60,
            'selling_price' => 100,
            'status' => 'available',
            'received_at' => now()->subDays(10),
            'last_moved_at' => now()->subDays(10),
        ]);

        // دفعة ثانية (تاريخ انتهاء أحدث)
        Inventory::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-B',
            'expiry_date' => now()->addYear(),
            'quantity' => 30,
            'reserved_quantity' => 0,
            'cost_price' => 58,
            'selling_price' => 100,
            'status' => 'available',
            'received_at' => now()->subDays(5),
            'last_moved_at' => now()->subDays(5),
        ]);

        // بيع 15 وحدة - FEFO تسحب من الدفعة الأولى (BATCH-A)
        $dto = $this->createSaleDTO([
            [
                'product_id' => $product->id,
                'quantity' => 15,
                'unit_price' => 100.00,
            ],
        ], [
            'amount_paid' => 1500.00,
        ]);

        $sale = $this->saleService->create($dto);

        // التأكد من الخصم: BATCH-A يجب أن يتبقى 5 وحدات (20 - 15)
        $batchA = Inventory::where('product_id', $product->id)
            ->where('batch_number', 'BATCH-A')->first();
        $this->assertEquals(5.0, (float) $batchA->quantity);

        $batchB = Inventory::where('product_id', $product->id)
            ->where('batch_number', 'BATCH-B')->first();
        $this->assertEquals(30.0, (float) $batchB->quantity);

        // ── Act: إلغاء الفاتورة ──
        $this->saleService->voidSale($sale->id, 'استعادة الدفعة الصحيحة');

        // ── Assert ──
        // يجب استعادة المخزون إلى الدفعة الصحيحة (BATCH-A)
        $batchA->refresh();
        $batchB->refresh();

        $this->assertEquals(20.0, (float) $batchA->quantity, 'BATCH-A should be restored to 20');
        $this->assertEquals(30.0, (float) $batchB->quantity, 'BATCH-B should remain at 30');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  3. اختبار الدفع بالمحفظة والمدفوعات المتعددة
    // ══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_creates_sale_with_split_payments(): void
    {
        // ── Arrange ──
        $productData = $this->createProductWithStock(
            name: 'Split Payment Item',
            price: 300,
            costPrice: 180,
            quantity: 10
        );

        $dto = $this->createSaleDTO([
            [
                'product_id' => $productData['product']->id,
                'quantity' => 1,
                'unit_price' => 300.00,
            ],
        ], [
            'amount_paid' => 300.00,
            'payments' => [
                ['payment_method' => 'cash', 'amount' => 150.00],
                ['payment_method' => 'card', 'amount' => 150.00],
            ],
        ]);

        // ── Act ──
        $sale = $this->saleService->create($dto);

        // ── Assert ──
        $payments = SalePayment::where('sale_id', $sale->id)->get();

        $this->assertCount(2, $payments);
        $this->assertEquals(150.00, (float) $payments->where('payment_method', 'cash')->first()->amount);
        $this->assertEquals(150.00, (float) $payments->where('payment_method', 'card')->first()->amount);
    }

    /** @test */
    public function it_creates_sale_with_wallet_payment(): void
    {
        // ── Arrange ──
        // إعداد المحفظة الإلكترونية
        Setting::updateOrCreate(
            [
                'company_id' => $this->company->id,
                'key' => 'wallet_phone',
                'group' => 'wallet',
            ],
            ['value' => '01012345678', 'type' => 'string']
        );
        Setting::updateOrCreate(
            [
                'company_id' => $this->company->id,
                'key' => 'wallet_enabled',
                'group' => 'wallet',
            ],
            ['value' => '1', 'type' => 'boolean']
        );

        $productData = $this->createProductWithStock(
            name: 'Wallet Payment Item',
            price: 200,
            costPrice: 120,
            quantity: 10
        );

        $dto = $this->createSaleDTO([
            [
                'product_id' => $productData['product']->id,
                'quantity' => 2,
                'unit_price' => 200.00,
            ],
        ], [
            'payment_method' => 'wallet',
            'amount_paid' => 400.00,
            'payments' => [
                ['payment_method' => 'wallet', 'amount' => 400.00],
            ],
        ]);

        // ── Act ──
        $sale = $this->saleService->create($dto);

        // ── Assert ──
        $this->assertEquals('wallet', $sale->payment_method);
        $this->assertEquals('completed', $sale->status);

        $payment = SalePayment::where('sale_id', $sale->id)->first();
        $this->assertEquals('wallet', $payment->payment_method);
        $this->assertEquals(400.00, (float) $payment->amount);

        // التحقق من خصم المخزون
        $this->assertInventoryQuantity($productData['product']->id, 8.0); // 10 - 2
    }

    /** @test */
    public function it_adds_payment_to_existing_sale(): void
    {
        // ── Arrange ──
        $productData = $this->createProductWithStock(
            name: 'Add Payment Item',
            price: 500,
            costPrice: 300,
            quantity: 5
        );

        // إنشاء فاتورة بدفع جزئي
        $dto = $this->createSaleDTO([
            [
                'product_id' => $productData['product']->id,
                'quantity' => 1,
                'unit_price' => 500.00,
            ],
        ], [
            'amount_paid' => 200.00,  // دفع 200 من أصل 500
        ]);

        $sale = $this->saleService->create($dto);
        $this->assertEquals('partial', $sale->status);

        // ── Act: إضافة دفعة جديدة ──
        $updatedSale = $this->saleService->addPayment(
            $sale->id,
            300.00,
            'card'
        );

        // ── Assert ──
        $this->assertEquals('completed', $updatedSale->status);
        $this->assertEquals(500.00, (float) $updatedSale->amount_paid);
        $this->assertEquals(0, (float) $updatedSale->change_amount);

        // تم تسجيل الدفعة في جدول المدفوعات
        SalePayment::create([
            'sale_id' => $sale->id,
            'payment_method' => 'card',
            'amount' => 300.00,
        ]);

        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'payment_method' => 'card',
            'amount' => 300.00,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  4. اختبار إنشاء فاتورة مع خصم
    // ══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_creates_sale_with_discount(): void
    {
        // ── Arrange ──
        $productData = $this->createProductWithStock(
            name: 'Discounted Item',
            price: 100,
            costPrice: 60,
            quantity: 20
        );

        $dto = $this->createSaleDTO([
            [
                'product_id' => $productData['product']->id,
                'quantity' => 5,
                'unit_price' => 100.00,
            ],
        ], [
            'discount_amount' => 50.00,  // خصم 50 على الفاتورة
            'amount_paid' => 450.00,     // 500 - 50 = 450
        ]);

        // ── Act ──
        $sale = $this->saleService->create($dto);

        // ── Assert ──
        $this->assertEquals(500.00, (float) $sale->subtotal);
        $this->assertEquals(50.00, (float) $sale->discount_amount);
        $this->assertEquals(450.00, (float) $sale->total_amount);
        $this->assertEquals(450.00, (float) $sale->amount_paid);
    }

    /** @test */
    public function it_generates_unique_invoice_numbers(): void
    {
        // ── Arrange ──
        $productData = $this->createProductWithStock('Invoice Test', 50, 30, 100);

        $dto = $this->createSaleDTO([
            [
                'product_id' => $productData['product']->id,
                'quantity' => 1,
                'unit_price' => 50.00,
            ],
        ], [
            'amount_paid' => 50.00,
        ]);

        // ── Act ──
        $sale1 = $this->saleService->create($dto);
        $sale2 = $this->saleService->create($dto);
        $sale3 = $this->saleService->create($dto);

        // ── Assert ──
        $this->assertNotNull($sale1->invoice_number);
        $this->assertNotNull($sale2->invoice_number);
        $this->assertNotNull($sale3->invoice_number);

        // جميع أرقام الفواتير مختلفة
        $this->assertNotEquals($sale1->invoice_number, $sale2->invoice_number);
        $this->assertNotEquals($sale2->invoice_number, $sale3->invoice_number);
        $this->assertNotEquals($sale1->invoice_number, $sale3->invoice_number);
    }
}

