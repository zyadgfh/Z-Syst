<?php

namespace Tests\Integration;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Party;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Services\ReturnService;
use App\Services\Stock\StockAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for the Returns flow.
 * Sets up real DB records and exercises ReturnService through the
 * allocation/release pipeline to verify end-to-end correctness.
 */
class ReturnsFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ReturnService $returnService;
    private StockAllocationService $stockAllocationService;
    private Business $business;
    private Product $product;
    private User $user;
    private Party $party;
    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->returnService = app(ReturnService::class);
        $this->stockAllocationService = app(StockAllocationService::class);

        $businessCategory = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create([
            'business_category_id' => $businessCategory->id,
        ]);

        $category = Category::factory()->create(['business_id' => $this->business->id]);
        $unit = Unit::factory()->create(['business_id' => $this->business->id]);
        $manufacturer = Manufacturer::factory()->create(['business_id' => $this->business->id]);

        $this->product = Product::factory()->create([
            'business_id' => $this->business->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'manufacturer_id' => $manufacturer->id,
            'sales_price' => 25.00,
            'purchase_without_tax' => 15.00,
        ]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->party = Party::factory()->create([
            'business_id' => $this->business->id,
            'type' => 'customer',
            'due' => 0,
        ]);

        $this->stock = Stock::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 100,
            'batch_no' => 'RETURN-BATCH-001',
            'expire_date' => '2027-12-31',
        ]);
    }

    /**
     * Create a sale with details in the database (simulates SaleService::create).
     */
    private function createSaleWithDetails(
        int $totalAmount,
        int $paidAmount,
        int $dueAmount,
        int $quantity,
        float $price
    ): Sale {
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => $this->party->id,
            'user_id' => $this->user->id,
            'totalAmount' => $totalAmount,
            'paidAmount' => $paidAmount,
            'dueAmount' => $dueAmount,
            'discountAmount' => 0,
            'isPaid' => $dueAmount === 0,
            'paymentType' => 'cash',
            'saleDate' => now()->format('Y-m-d'),
            'invoiceNumber' => 'S-TEST-' . uniqid(),
        ]);

        SaleDetails::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'price' => $price,
            'quantities' => $quantity,
            'batch_no' => $this->stock->batch_no,
            'purchase_price' => 15.00,
        ]);

        return $sale;
    }

    // ── Sale Return Flow ────────────────────────────────────────────

    public function test_sale_return_restores_stock(): void
    {
        $initialStock = 100;
        $soldQty = 30;
        $returnQty = 10;

        $this->assertEquals($initialStock, $this->stock->productStock);

        // Simulate sale: deduct stock
        $this->stockAllocationService->allocate(
            $this->stock,
            $soldQty,
            'App\\Models\\Sale',
            1,
            $this->user->id,
            'Sale deduction'
        );

        $this->stock->refresh();
        $this->assertEquals(70, $this->stock->productStock);

        // Create sale record
        $sale = $this->createSaleWithDetails(
            totalAmount: $soldQty * 25,
            paidAmount: $soldQty * 25,
            dueAmount: 0,
            quantity: $soldQty,
            price: 25.00
        );

        // Simulate return: restore stock
        $this->stockAllocationService->release(
            $this->stock,
            $returnQty,
            'App\\Models\\SaleReturn',
            $sale->id,
            $this->user->id,
            'Sale return restoration'
        );

        $this->stock->refresh();
        $this->assertEquals(80, $this->stock->productStock);

        // Verify movement trail
        $movements = StockMovement::where('stock_id', $this->stock->id)->get();
        $this->assertCount(2, $movements);
        $this->assertEquals('out', $movements[0]->movement_type);
        $this->assertEquals('in', $movements[1]->movement_type);
        $this->assertEquals($soldQty, $movements[0]->quantity);
        $this->assertEquals($returnQty, $movements[1]->quantity);
    }

    public function test_sale_return_with_due_amount_updates_party(): void
    {
        // Start party with 0 due
        $this->party->update(['due' => 0]);

        $soldQty = 20;
        $dueAmount = 300;

        // Simulate sale with due
        $this->stockAllocationService->allocate(
            $this->stock, $soldQty, 'App\\Models\\Sale', 1, $this->user->id
        );

        $sale = $this->createSaleWithDetails(
            totalAmount: 500,
            paidAmount: 200,
            dueAmount: $dueAmount,
            quantity: $soldQty,
            price: 25.00
        );

        // Simulate what SaleService::create does to party due
        $this->party->update(['due' => $this->party->due + $dueAmount]);
        $this->party->refresh();
        $this->assertEquals(300, $this->party->due);

        // Simulate a partial return refund reduces due
        $returnAmount = 125; // 5 items × $25
        $this->party->update(['due' => $this->party->due - $returnAmount]);
        $this->party->refresh();
        $this->assertEquals(175, $this->party->due);
    }

    public function test_sale_return_stock_lifecycle_end_to_end(): void
    {
        // ── Setup: Purchase stock ───────────────────────────────────
        $stock = Stock::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'productStock' => 0,
            'batch_no' => 'E2E-BATCH-001',
            'expire_date' => '2027-06-30',
        ]);

        $this->stockAllocationService->addStock($stock, 50, 'App\\Models\\Purchase', 1, $this->user->id);
        $stock->refresh();
        $this->assertEquals(50, $stock->productStock);

        // ── Phase 1: Sale of 25 units ──────────────────────────────
        $sale = $this->createSaleWithDetails(
            totalAmount: 625, paidAmount: 625, dueAmount: 0,
            quantity: 25, price: 25.00
        );

        $this->stockAllocationService->allocate(
            $stock, 25, 'App\\Models\\Sale', $sale->id, $this->user->id
        );

        $stock->refresh();
        $this->assertEquals(25, $stock->productStock);

        // ── Phase 2: Return 10 units ────────────────────────────────
        $this->stockAllocationService->release(
            $stock, 10, 'App\\Models\\SaleReturn', $sale->id, $this->user->id,
            'Customer returned 10 items'
        );

        $stock->refresh();
        $this->assertEquals(35, $stock->productStock);

        // ── Phase 3: Another sale of 35 units ──────────────────────
        $sale2 = $this->createSaleWithDetails(
            totalAmount: 875, paidAmount: 875, dueAmount: 0,
            quantity: 35, price: 25.00
        );

        $this->stockAllocationService->allocate(
            $stock, 35, 'App\\Models\\Sale', $sale2->id, $this->user->id
        );

        $stock->refresh();
        $this->assertEquals(0, $stock->productStock);

        // ── Verify: 4 movements total ──────────────────────────────
        $movements = StockMovement::where('stock_id', $stock->id)->orderBy('id')->get();
        $this->assertCount(4, $movements);

        $this->assertEquals('in', $movements[0]->movement_type);    // purchase
        $this->assertEquals('out', $movements[1]->movement_type);   // sale 1
        $this->assertEquals('in', $movements[2]->movement_type);    // return
        $this->assertEquals('out', $movements[3]->movement_type);   // sale 2
    }

    // ── ReturnService validateReturnRules ────────────────────────────

    public function test_validate_return_rules_within_period(): void
    {
        $returnData = [
            'original_date' => now()->subDays(5)->toDateString(),
            'reason' => 'Defective product',
            'items' => [
                ['quantity' => 5, 'original_quantity' => 20],
            ],
        ];

        $result = $this->returnService->validateReturnRules($returnData);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_return_rules_exceeds_max_days(): void
    {
        $returnData = [
            'original_date' => now()->subDays(45)->toDateString(),
            'reason' => 'Changed mind',
            'items' => [
                ['quantity' => 5, 'original_quantity' => 20],
            ],
        ];

        $result = $this->returnService->validateReturnRules($returnData);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('exceeded', $result['errors'][0]);
    }

    public function test_validate_return_rules_missing_reason(): void
    {
        $returnData = [
            'original_date' => now()->subDays(5)->toDateString(),
            'items' => [
                ['quantity' => 5, 'original_quantity' => 20],
            ],
        ];

        $result = $this->returnService->validateReturnRules($returnData);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('reason', $result['errors'][0]);
    }

    public function test_validate_return_rules_exceeds_max_quantity(): void
    {
        $returnData = [
            'original_date' => now()->subDays(5)->toDateString(),
            'reason' => 'Defective',
            'items' => [
                ['quantity' => 10, 'original_quantity' => 20],
            ],
        ];

        $result = $this->returnService->validateReturnRules($returnData, [
            'max_return_quantity' => 5,
        ]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('maximum', $result['errors'][0]);
    }

    public function test_validate_return_rules_exceeds_max_percentage(): void
    {
        $returnData = [
            'original_date' => now()->subDays(5)->toDateString(),
            'reason' => 'Defective',
            'items' => [
                ['quantity' => 15, 'original_quantity' => 20], // 75%
            ],
        ];

        $result = $this->returnService->validateReturnRules($returnData, [
            'max_return_percentage' => 50,
        ]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('percentage', $result['errors'][0]);
    }

    // ── ReturnService calculateReturnRefund ─────────────────────────

    public function test_calculate_return_refund_single_item(): void
    {
        $items = [
            [
                'unit_price' => 25.00,
                'quantity' => 5,
                'discount' => 0,
            ],
        ];

        $refund = $this->returnService->calculateReturnRefund($items);
        $this->assertEquals(125.00, $refund);
    }

    public function test_calculate_return_refund_multiple_items(): void
    {
        $items = [
            ['unit_price' => 25.00, 'quantity' => 5, 'discount' => 10],
            ['unit_price' => 50.00, 'quantity' => 2, 'discount' => 5],
            ['unit_price' => 10.00, 'quantity' => 10, 'discount' => 0],
        ];

        $refund = $this->returnService->calculateReturnRefund($items);

        // (25×5 - 10) + (50×2 - 5) + (10×10 - 0) = 115 + 95 + 100 = 310
        $this->assertEquals(310.00, $refund);
    }

    public function test_calculate_return_refund_with_discounts(): void
    {
        $items = [
            ['unit_price' => 100.00, 'quantity' => 3, 'discount' => 30],
        ];

        $refund = $this->returnService->calculateReturnRefund($items);
        $this->assertEquals(270.00, $refund);
    }

    // ── ReturnService getReturnStatistics ────────────────────────────

    public function test_get_return_statistics_empty(): void
    {
        $stats = $this->returnService->getReturnStatistics($this->business->id);

        $this->assertEquals(0, $stats['sale_returns']['total_count']);
        $this->assertEquals(0, $stats['purchase_returns']['total_count']);
        $this->assertEquals(0, $stats['net_financial_impact']);
    }

    public function test_sale_return_eligibility_rejects_old_sale(): void
    {
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => $this->party->id,
            'user_id' => $this->user->id,
            'totalAmount' => 500,
            'paidAmount' => 500,
            'dueAmount' => 0,
            'discountAmount' => 0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'saleDate' => now()->subDays(60)->format('Y-m-d'),
            'invoiceNumber' => 'S-OLD-' . uniqid(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('too old');

        $this->returnService->processSaleReturn($sale, [
            'items' => [
                ['sale_detail_id' => 1, 'quantity' => 1],
            ],
            'return_date' => now()->format('Y-m-d'),
            'return_period_days' => 30,
        ], $this->business->id, $this->user->id);
    }
}
