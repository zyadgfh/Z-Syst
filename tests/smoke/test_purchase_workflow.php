<?php
/**
 * Smoke Test: Purchase → Cancel → Verify Reversal
 *
 * Run with: php tests/smoke/test_purchase_workflow.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

$passed = 0;
$failed = 0;

function test(string $name, callable $fn) {
    global $passed, $failed;
    try {
        $fn();
        echo "  ✅ {$name}" . PHP_EOL;
        $passed++;
    } catch (\Throwable $e) {
        echo "  ❌ {$name}: {$e->getMessage()}" . PHP_EOL;
        echo "     at {$e->getFile()}:{$e->getLine()}" . PHP_EOL;
        $failed++;
    }
}

function assert_true(bool $condition, string $msg) {
    if (!$condition) throw new \RuntimeException("Assertion failed: {$msg}");
}

function assert_equals($expected, $actual, string $msg) {
    if ($expected != $actual) {
        throw new \RuntimeException("Expected [{$msg}] = [{$expected}], got [{$actual}]");
    }
}

echo PHP_EOL . "═══════════════════════════════════════════" . PHP_EOL;
echo "  PURCHASE WORKFLOW SMOKE TEST" . PHP_EOL;
echo "═══════════════════════════════════════════" . PHP_EOL . PHP_EOL;

// ─── Setup: Create required business + user ──────────────

$category = BusinessCategory::firstOrCreate(
    ['name' => 'Smoke Test Category'],
    ['description' => 'Test', 'status' => 'active']
);
$business = Business::firstOrCreate(
    ['companyName' => 'Smoke Test Pharmacy'],
    ['business_category_id' => $category->id, 'remainingShopBalance' => 0]
);
$businessId = $business->id;

$user = User::firstOrCreate(
    ['email' => 'smoke_admin@test.com'],
    [
        'name' => 'Smoke Test Admin',
        'password' => bcrypt('password'),
        'business_id' => $businessId,
        'role' => 'superadmin',
        'status' => 'active',
    ]
);
$userId = $user->id;

// Create test supplier
$supplier = Party::firstOrCreate(
    ['business_id' => $businessId, 'name' => 'SMOKE TEST SUPPLIER', 'type' => 'supplier'],
    ['email' => 'smoke_supplier@test.com', 'phone' => '0000000000', 'due' => 0, 'status' => 'active']
);
$supplier->update(['due' => 0]);
$initialSupplierDue = (float) $supplier->fresh()->due;

// Create test product
$product = Product::firstOrCreate(
    ['business_id' => $businessId, 'productName' => 'SMOKE TEST PRODUCT'],
    [
        'productCode' => 'SMOKE-' . time(),
        'purchase_without_tax' => 10.00,
        'purchase_with_tax' => 10.00,
        'sales_price' => 15.00,
        'profit_percent' => 50,
    ]
);

// Record initial counts
$initialStock = Stock::where('business_id', $businessId)
    ->where('product_id', $product->id)
    ->sum('productStock');

$initialLedgerCount = SupplierLedger::where('business_id', $businessId)
    ->where('party_id', $supplier->id)
    ->count();

echo "Setup: business_id={$businessId}, supplier_id={$supplier->id}, product_id={$product->id}" . PHP_EOL;
echo "Initial stock: {$initialStock}, Initial supplier due: {$initialSupplierDue}" . PHP_EOL . PHP_EOL;

// Fake auth so Purchase::boot() can generate invoiceNumber
Auth::shouldReceive('check')->andReturn(true);
Auth::shouldReceive('user')->andReturn($user);

// ─── Test 1: Create Purchase ────────────────────────────

echo "── Test 1: Create Purchase Invoice ──" . PHP_EOL;

$purchaseService = app(PurchaseService::class);

$purchaseData = [
    'party_id' => $supplier->id,
    'branch_id' => null,
    'purchaseDate' => now()->toDateString(),
    'paymentType' => 'Cash',
    'discountAmount' => 0,
    'tax_amount' => 0,
    'paidAmount' => 0,
    'note' => 'Smoke test purchase',
    'products' => [
        [
            'product_id' => $product->id,
            'barcode' => $product->productCode,
            'batch_no' => 'SMOKE-BATCH-001',
            'quantities' => 10,
            'purchase_with_tax' => 10.00,
            'purchase_without_tax' => 10.00,
            'discount' => 0,
            'tax' => 0,
            'profit_percent' => 50,
            'sales_price' => 15.00,
        ],
    ],
];

$purchase = $purchaseService->create($purchaseData, $businessId, $userId);

test('Purchase created with correct total', function () use ($purchase) {
    assert_equals(100.00, $purchase->totalAmount, 'totalAmount');
});

test('Purchase invoice number generated', function () use ($purchase) {
    assert_true(!empty($purchase->invoiceNumber), 'invoiceNumber is not empty');
});

test('Purchase status is received', function () use ($purchase) {
    assert_equals('received', $purchase->status, 'status');
});

test('Purchase details created', function () use ($purchase) {
    $detailCount = PurchaseDetails::where('purchase_id', $purchase->id)->count();
    assert_equals(1, $detailCount, 'detail count');
});

test('Stock increased after purchase', function () use ($businessId, $product, $initialStock) {
    $newStock = Stock::where('business_id', $businessId)
        ->where('product_id', $product->id)
        ->sum('productStock');
    assert_true($newStock > $initialStock, "stock increased from {$initialStock} to {$newStock}");
});

test('Stock movement recorded', function () use ($purchase) {
    $movements = StockMovement::where('reference_type', Purchase::class)
        ->where('reference_id', $purchase->id)
        ->count();
    assert_true($movements >= 1, "at least 1 stock movement, got {$movements}");
});

test('Supplier ledger entry created', function () use ($businessId, $supplier, $purchase) {
    $lastEntry = SupplierLedger::where('business_id', $businessId)
        ->where('party_id', $supplier->id)
        ->latest()
        ->first();
    assert_true($lastEntry !== null, 'ledger entry exists');
    assert_equals($purchase->invoiceNumber, $lastEntry->invoice_number, 'ledger invoice number');
});

test('Supplier due updated', function () use ($supplier) {
    $supplier->refresh();
    assert_true($supplier->due >= 100.00, "supplier due >= 100, got {$supplier->due}");
});

test('Financial transaction created', function () use ($purchase) {
    $ftx = FinancialTransaction::where('reference_type', 'purchase')
        ->where('reference_id', $purchase->id)
        ->count();
    assert_equals(1, $ftx, 'financial transaction count');
});

// ─── Test 2: Cancel Purchase ────────────────────────────

echo PHP_EOL . "── Test 2: Cancel Purchase Invoice ──" . PHP_EOL;

$stockBeforeCancel = Stock::where('business_id', $businessId)
    ->where('product_id', $product->id)
    ->sum('productStock');

$purchaseService->delete($purchase, $businessId, $userId);

test('Purchase status changed to canceled', function () use ($purchase) {
    $purchase->refresh();
    assert_equals('canceled', $purchase->status, 'status after cancel');
});

test('Stock restored after cancellation', function () use ($businessId, $product, $initialStock) {
    $afterCancelStock = Stock::where('business_id', $businessId)
        ->where('product_id', $product->id)
        ->sum('productStock');
    // Stock should be back to initial (10), since cancel deducts the 10 we added
    assert_equals($initialStock, $afterCancelStock, "stock restored to {$initialStock}, got {$afterCancelStock}");
});

test('Stock reversal movement recorded', function () use ($purchase) {
    $reversals = StockMovement::where('reference_type', Purchase::class)
        ->where('reference_id', $purchase->id)
        ->where('movement_type', 'out')
        ->count();
    assert_true($reversals >= 1, "reversal stock movement recorded (got {$reversals})");
});

test('Supplier ledger reversal entry', function () use ($businessId, $supplier) {
    $lastEntry = SupplierLedger::where('business_id', $businessId)
        ->where('party_id', $supplier->id)
        ->latest()
        ->first();
    assert_true($lastEntry->credit > 0, "ledger has credit entry (reversal)");
});

test('Financial transaction deleted on cancel', function () use ($purchase) {
    $ftx = FinancialTransaction::where('reference_type', 'purchase')
        ->where('reference_id', $purchase->id)
        ->count();
    assert_equals(0, $ftx, 'financial transaction deleted');
});

test('Supplier due restored after cancellation', function () use ($supplier, $initialSupplierDue) {
    $supplier->refresh();
    assert_equals($initialSupplierDue, $supplier->due, "supplier due restored to {$initialSupplierDue}");
});

// ─── Test 3: Create Purchase Return ─────────────────────

echo PHP_EOL . "── Test 3: Create Purchase Return ──" . PHP_EOL;

// Create a new purchase for return testing
$purchase2Data = $purchaseData;
$purchase2Data['products'][0]['batch_no'] = 'SMOKE-BATCH-002';

$purchase2 = $purchaseService->create($purchase2Data, $businessId, $userId);

$stockAfterPurchase2 = Stock::where('business_id', $businessId)
    ->where('product_id', $product->id)
    ->where('batch_no', 'SMOKE-BATCH-002')
    ->first()
    ?->fresh()->productStock ?? 0;

$returnService = app(PurchaseReturnService::class);

$returnData = [
    'items' => [
        [
            'purchase_detail_id' => PurchaseDetails::where('purchase_id', $purchase2->id)->first()->id,
            'return_qty' => 3,
            'discount' => 0,
            'tax' => 0,
        ],
    ],
    'reason' => 'Smoke test return',
    'notes' => 'Testing return flow',
];

$return = $returnService->processReturn($purchase2, $returnData, $businessId, $userId);

test('Purchase return created', function () use ($return) {
    assert_true($return->id > 0, 'return has ID');
});

test('Return invoice number generated', function () use ($return) {
    assert_true(!empty($return->invoice_no), 'invoice_no is not empty');
});

test('Return credit amount correct', function () use ($return) {
    assert_equals(30.00, $return->credit_amount, 'credit_amount (3 × 10.00)');
});

test('Return status is completed', function () use ($return) {
    assert_equals('completed', $return->status, 'status');
});

test('Return details created', function () use ($return) {
    $detailCount = PurchaseReturnDetail::where('purchase_return_id', $return->id)->count();
    assert_equals(1, $detailCount, 'return detail count');
});

test('Stock deducted after return', function () use ($businessId, $product, $stockAfterPurchase2) {
    $stockAfterReturn = Stock::where('business_id', $businessId)
        ->where('product_id', $product->id)
        ->where('batch_no', 'SMOKE-BATCH-002')
        ->first()
        ?->fresh()->productStock ?? 0;
    assert_equals($stockAfterPurchase2 - 3, $stockAfterReturn, "stock decreased by 3");
});

test('Supplier ledger credit for return', function () use ($businessId, $supplier) {
    $lastEntry = SupplierLedger::where('business_id', $businessId)
        ->where('party_id', $supplier->id)
        ->where('transaction_type', 'purchase_return')
        ->latest()
        ->first();
    assert_true($lastEntry !== null, 'ledger has purchase_return entry');
    assert_equals(30.00, $lastEntry->credit, 'ledger credit amount');
});

test('Parent purchase status updated to returned_partially', function () use ($purchase2) {
    $purchase2->refresh();
    assert_equals('returned_partially', $purchase2->status, 'purchase status');
});

test('Returnable quantity reduced', function () use ($returnService, $purchase2) {
    $items = $returnService->getReturnableItems($purchase2->id);
    $firstItem = $items[0];
    assert_equals(7, $firstItem['returnable_qty'], "returnable_qty is 7 (10 - 3)");
});

test('Second return respects reduced returnable', function () use ($returnService, $purchase2, $businessId, $userId) {
    $detailId = PurchaseDetails::where('purchase_id', $purchase2->id)->first()->id;
    $returnData2 = [
        'items' => [
            ['purchase_detail_id' => $detailId, 'return_qty' => 7, 'discount' => 0, 'tax' => 0],
        ],
        'reason' => 'Second return',
    ];
    $return2 = $returnService->processReturn($purchase2, $returnData2, $businessId, $userId);
    assert_equals(70.00, $return2->credit_amount, 'second return credit (7 × 10.00)');

    // Now verify no more can be returned
    $items = $returnService->getReturnableItems($purchase2->id);
    assert_equals(0, $items[0]['returnable_qty'], 'returnable_qty is 0 after full return');
});

// ─── Summary ────────────────────────────────────────────

echo PHP_EOL . "═══════════════════════════════════════════" . PHP_EOL;
echo "  RESULTS: {$passed} passed, {$failed} failed" . PHP_EOL;
echo "═══════════════════════════════════════════" . PHP_EOL . PHP_EOL;

// Cleanup
SupplierLedger::where('business_id', $businessId)->where('party_id', $supplier->id)->delete();
FinancialTransaction::where('reference_type', 'purchase')
    ->whereIn('reference_id', [$purchase->id, $purchase2->id])->delete();
StockMovement::where('reference_type', Purchase::class)
    ->whereIn('reference_id', [$purchase->id, $purchase2->id])->delete();
PurchaseReturnDetail::whereHas('purchaseReturn', fn($q) => $q->where('purchase_id', $purchase2->id))->delete();
PurchaseReturn::where('purchase_id', $purchase2->id)->delete();
PurchaseDetails::whereIn('purchase_id', [$purchase->id, $purchase2->id])->delete();
Purchase::whereIn('id', [$purchase->id, $purchase2->id])->delete();
Stock::where('business_id', $businessId)->where('product_id', $product->id)->delete();
$supplier->delete();

echo "  🧹 Test data cleaned up." . PHP_EOL . PHP_EOL;

exit($failed > 0 ? 1 : 0);
