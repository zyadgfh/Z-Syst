<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Services\FefoStockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * POS Workflow Tests - FEFO Integration
 *
 * اختبارات مسار العمل في نقطة البيع:
 * - اختبار إنشاء فاتورة مع تخصيص المخزون FEFO
 * - اختبار إلغاء فاتورة مع استعادة المخزون
 * - اختبار مرتجع مبيعات
 * - اختبار الدفع بالمحفظة
 */
class PosWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;
    private Branch $branch;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role' => 'cashier',
        ]);

        app()->instance('tenant.company_id', $this->company->id);
    }

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

        return ['product' => $product, 'inventory' => $inventory];
    }

    /**
     * Test 1: FEFO allocation prioritizes earliest expiry
     */
    public function test_fefo_prioritizes_earliest_expiry(): void
    {
        $data = $this->createProductWithStock(
            name: 'FEFO Test Product',
            price: 15.00,
            costPrice: 10.00,
            quantity: 100.0,
            batchNumber: 'BATCH-FAR',
            expiryDate: now()->addDays(120)->toDateString()
        );
        $product = $data['product'];

        Inventory::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-NEAR',
            'expiry_date' => now()->addDays(30)->toDateString(),
            'quantity' => 100,
            'reserved_quantity' => 0,
            'cost_price' => 10.00,
            'selling_price' => 15.00,
            'status' => 'available',
            'received_at' => now(),
            'last_moved_at' => now(),
        ]);

        $fefoService = App::make(FefoStockService::class);

        $allocations = $fefoService->allocate(
            (string) $product->id,
            (string) $this->branch->id,
            50
        );

        $this->assertEquals('BATCH-NEAR', $allocations[0]['batch_number']);
        $this->assertEquals(50.0, $allocations[0]['quantity']);
    }

    /**
     * Test 2: Concurrent sales prevent overselling
     */
    public function test_concurrent_sales_prevent_overselling(): void
    {
        $data = $this->createProductWithStock(
            name: 'Low Stock Product',
            price: 15.00,
            costPrice: 10.00,
            quantity: 10.0
        );
        $product = $data['product'];

        $fefoService = App::make(FefoStockService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $fefoService->allocate(
            (string) $product->id,
            (string) $this->branch->id,
            15
        );
    }

    /**
     * Test 3: Cancel invoice with FEFO restore
     */
    public function test_cancel_invoice_with_fefo_restore(): void
    {
        $data = $this->createProductWithStock(
            name: 'Cancel Test Product',
            price: 15.00,
            costPrice: 10.00,
            quantity: 100.0
        );
        $inventory = $data['inventory'];

        $initialQuantity = $inventory->quantity;

        // Simulate sale deduction (reduce stock by 20 units)
        $inventory->quantity -= 20;
        $inventory->save();

        $inventory->refresh();
        $this->assertEquals(80.0, $inventory->quantity);

        // Restore stock via FefoStockService (simulating invoice cancellation)
        $fefoService = App::make(FefoStockService::class);
        $fefoService->restore(
            allocations: [[
                'inventory_id' => $inventory->id,
                'product_id' => $data['product']->id,
                'branch_id' => $this->branch->id,
                'batch_number' => $inventory->batch_number,
                'quantity' => 20.0,
                'unit_price' => 15.00,
                'cost_price' => 10.00,
            ]],
            referenceType: 'sale_void',
            referenceId: 'sale-' . now()->timestamp
        );

        $inventory->refresh();
        $this->assertEquals($initialQuantity, $inventory->quantity);
    }

    /**
     * Test 4: Sales return with stock restoration
     */
    public function test_sales_return_with_stock_restoration(): void
    {
        $data = $this->createProductWithStock(
            name: 'Return Test Product',
            price: 15.00,
            costPrice: 10.00,
            quantity: 100.0
        );
        $inventory = $data['inventory'];

        // Simulate sale deduction (reduce stock by 10 units)
        $inventory->quantity -= 10;
        $inventory->save();

        $inventory->refresh();
        $this->assertEquals(90.0, $inventory->quantity);

        // Restore 5 units via FefoStockService (simulating sales return)
        $fefoService = App::make(FefoStockService::class);
        $fefoService->restore(
            allocations: [[
                'inventory_id' => $inventory->id,
                'product_id' => $data['product']->id,
                'branch_id' => $this->branch->id,
                'batch_number' => $inventory->batch_number,
                'quantity' => 5.0,
                'unit_price' => 15.00,
                'cost_price' => 10.00,
            ]],
            referenceType: 'sale_return',
            referenceId: 'sale-return-' . now()->timestamp
        );

        $inventory->refresh();
        $this->assertEquals(95.0, $inventory->quantity);
    }

    /**
     * Test 5: Wallet payment processing
     */
    public function test_wallet_payment_processing(): void
    {
        Sanctum::actingAs($this->user, [], 'sanctum');

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'track_inventory' => false,
            'sales_price' => 100.00,
            'is_active' => true,
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 100.00,
                ],
            ],
            'payment_method' => 'vodafone_cash',
            'amount_paid' => 200.00,
        ];

        $response = $this->postJson('/api/v1/sales', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.payment_method', 'vodafone_cash')
            ->assertJsonPath('data.status', 'completed');
    }

    /**
     * Test 6: Complete sale with FEFO deduction
     */
    public function test_create_invoice_with_fefo_deduction(): void
    {
        Sanctum::actingAs($this->user, [], 'sanctum');

        $data1 = $this->createProductWithStock(
            name: 'FEFO Product',
            price: 15.00,
            costPrice: 10.00,
            quantity: 50.0,
            batchNumber: 'BATCH-EXP-001',
            expiryDate: now()->addDays(30)->toDateString()
        );

        $this->createProductWithStock(
            name: 'FEFO Product',
            price: 15.00,
            costPrice: 10.00,
            quantity: 30.0,
            batchNumber: 'BATCH-EXP-002',
            expiryDate: now()->addDays(60)->toDateString()
        );

        $product = $data1['product'];

        $payload = [
            'branch_id' => $this->branch->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 40,
                    'unit_price' => 15.00,
                ],
            ],
            'payment_method' => 'cash',
            'amount_paid' => 600.00,
        ];

        $response = $this->postJson('/api/v1/sales', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.total_amount', 600.00);
    }
}
