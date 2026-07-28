<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\CashRegister;
use App\Models\StockMovement;
use App\Modules\Sales\Domain\DTOs\CreateSaleDTO;
use App\Modules\Sales\Domain\Events\SaleCreated;
use App\Modules\Sales\Domain\Events\SaleVoided;
use App\Services\FefoStockService;
use App\Services\StockBatchService;
use App\Services\StockMovementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sale Service — Core business logic for sales operations.
 *
 * All business rules:
 * - Stock verification via FEFO before sale
 * - Overselling prevention
 * - Automatic inventory deduction on confirmation
 * - Complete audit trail via StockMovementService
 * - Receipt generation support
 */
class SaleService
{
    public function __construct(
        protected StockBatchService $stockBatchService,
        protected StockMovementService $stockMovementService,
        protected FefoStockService $fefoStockService,
    ) {}

    /**
     * List sales with filters and pagination.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return Sale::query()
            ->select([
                'id',
                'company_id',
                'branch_id',
                'customer_id',
                'customer_name',
                'customer_phone',
                'invoice_number',
                'subtotal',
                'discount_amount',
                'tax_amount',
                'total_amount',
                'amount_paid',
                'change_amount',
                'payment_method',
                'status',
                'notes',
                'prescription_id',
                'created_by',
                'created_at',
                'updated_at',
            ])
            ->with(['items.product:id,name,product_name,barcode', 'createdBy:id,name'])
            ->when(
                !empty($filters['company_id']),
                fn (Builder $q) => $q->where('company_id', $filters['company_id'])
            )
            ->when(
                !empty($filters['branch_id']),
                fn (Builder $q) => $q->where('branch_id', $filters['branch_id'])
            )
            ->when(
                !empty($filters['customer_id']),
                fn (Builder $q) => $q->where('customer_id', $filters['customer_id'])
            )
            ->when(
                !empty($filters['status']),
                fn (Builder $q) => $q->where('status', $filters['status'])
            )
            ->when(
                !empty($filters['payment_method']),
                fn (Builder $q) => $q->where('payment_method', $filters['payment_method'])
            )
            ->when(
                isset($filters['date_from']),
                fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['date_from'])
            )
            ->when(
                isset($filters['date_to']),
                fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['date_to'])
            )
            ->when(
                !empty($filters['search']),
                fn (Builder $q) => $q->where(function (Builder $query) use ($filters) {
                    $search = $filters['search'];
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                })
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new sale with full validation, stock deduction, and audit trail.
     *
     * Steps:
     * 1. Validate all products exist and are active
     * 2. Verify sufficient stock via FEFO allocation
     * 3. Calculate totals (subtotal, discount, tax, total)
     * 4. Deduct stock from inventory using FEFO
     * 5. Log stock movements for each item
     * 6. Create Sale + SaleItem records
     * 7. Dispatch SaleCreated event
     *
     * @throws \RuntimeException if insufficient stock or invalid products
     */
    public function create(CreateSaleDTO $dto): Sale
    {
        return DB::transaction(function () use ($dto) {
            // ── Step 1: Validate products ──
            $items = [];
            $saleItemsData = [];
            $subtotal = 0;

            foreach ($dto->items as $itemData) {
                $product = Product::find($itemData['product_id']);
                if (!$product || !$product->is_active) {
                    throw new \RuntimeException(
                        "Product not found or inactive: {$itemData['product_id']}"
                    );
                }

                $quantity = (float) $itemData['quantity'];
                $unitPrice = (float) ($itemData['unit_price'] ?? $product->sales_price ?? 0);
                $discount = (float) ($itemData['discount'] ?? 0);
                $taxItem = (float) ($itemData['tax'] ?? 0);
                $lineTotal = ($unitPrice * $quantity) - $discount + $taxItem;

                if ($quantity <= 0) {
                    throw new \RuntimeException("Invalid quantity for product: {$product->name}");
                }

                // ── Step 2: Verify & allocate stock via FEFO (First Expiry, First Out) ──
                $branchId = $dto->branch_id;
                $allocations = [];

                if ($product->track_inventory && $branchId) {
                    try {
                        $allocations = $this->stockBatchService->allocateFefo(
                            productId: (string) $product->id,
                            branchId: (string) $branchId,
                            quantityNeeded: $quantity,
                        );
                    } catch (\RuntimeException $e) {
                        throw new \RuntimeException(
                            "Insufficient stock for {$product->name}: {$e->getMessage()}"
                        );
                    }
                }

                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'tax' => $taxItem,
                    'line_total' => $lineTotal,
                    'allocations' => $allocations,
                ];

                $saleItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'tax' => $taxItem,
                    'line_total' => $lineTotal,
                ];

                $subtotal += $lineTotal;
            }

            // ── Step 3: Calculate totals ──
            $discountAmount = $dto->discount_amount ?? 0;
            $taxAmount = $dto->tax_amount ?? ($dto->tax_rate > 0 ? $subtotal * ($dto->tax_rate / 100) : 0);
            $totalAmount = max(0, $subtotal - $discountAmount + $taxAmount);
            $amountPaid = $dto->amount_paid ?? $totalAmount;
            $changeAmount = max(0, $amountPaid - $totalAmount);

            // ── Step 4: Create the Sale record ──
            $sale = Sale::create([
                'company_id' => $dto->company_id,
                'branch_id' => $dto->branch_id,
                'customer_id' => $dto->customer_id,
                'customer_name' => $dto->customer_name ?? 'نقدي',
                'customer_phone' => $dto->customer_phone,
                'invoice_number' => $this->generateInvoiceNumber($dto->company_id, $dto->branch_id),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
                'payment_method' => $dto->payment_method ?? 'cash',
                'status' => $amountPaid >= $totalAmount ? 'completed' : 'partial',
                'notes' => $dto->notes,
                'prescription_id' => $dto->prescription_id,
                'created_by' => $dto->created_by,
            ]);

            // ── Step 5: Create SaleItems & Deduct Stock ──
            foreach ($items as $index => $item) {
                $productName = $item['product']->name
                    ?? $item['product']->product_name
                    ?? $item['product']->generic_name
                    ?? 'Product';

                $saleItem = SaleItem::create([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'sale_id' => $sale->id,
                    'product_id' => $item['product']->id,
                    'name_snapshot' => $productName,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => (float) ($item['product']->cost_price ?? 0),
                    'discount' => $item['discount'],
                    'tax_rate' => $dto->tax_rate ?? 0,
                    'tax_amount' => $item['tax'],
                    'total' => $item['unit_price'] * $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);

                // ── Step 6: Confirm stock deduction (FEFO) ──
                if (!empty($item['allocations'])) {
                    $allocations = array_map(function ($alloc) use ($sale) {
                        $alloc['reference_id'] = $sale->id;
                        return $alloc;
                    }, $item['allocations']);

                    $this->stockBatchService->confirmDeduction($allocations);
                }
            }

            // ── Step 7: Record SalePayments (supports split payments) ──
            $payments = $dto->payments ?? [];
            if (empty($payments) && $amountPaid > 0) {
                // Default single payment
                $payments[] = [
                    'payment_method' => $dto->payment_method ?? 'cash',
                    'amount' => $amountPaid,
                ];
            }

            foreach ($payments as $paymentData) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => $paymentData['payment_method'] ?? 'cash',
                    'amount' => $paymentData['amount'],
                    'reference_number' => $paymentData['reference_number'] ?? null,
                    'transaction_id' => $paymentData['transaction_id'] ?? null,
                ]);
            }

            // ── Step 8: Update Cash Register if open ──
            if (!empty($dto->cash_register_id)) {
                $cashRegister = CashRegister::find($dto->cash_register_id);
                if ($cashRegister && $cashRegister->status === 'open') {
                    $cashRegister->increment('total_sales', $totalAmount);
                }
            }

            // ── Step 9: Dispatch event ──
            event(new SaleCreated($sale));

            return $sale->load([
                'items.product:id,name,product_name,barcode,sales_price',
                'payments',
            ]);
        });
    }

    /**
     * Get a single sale with all relations.
     */
    public function find(string $id): Sale
    {
        return Sale::with([
            'items.product:id,name,product_name,generic_name,barcode,product_code,sales_price',
            'createdBy:id,name',
            'customer',
        ])->findOrFail($id);
    }

    /**
     * Get receipt data for a sale (for printing/PDF generation).
     */
    public function getReceipt(string $id): array
    {
        $sale = $this->find($id);

        $items = $sale->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? $item->product->product_name ?? 'منتج',
                'barcode' => $item->product->barcode ?? null,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) ($item->discount ?? 0),
                'line_total' => (float) $item->line_total,
            ];
        });

        return [
            'id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'date' => $sale->created_at->format('Y-m-d H:i:s'),
            'customer_name' => $sale->customer_name ?? 'نقدي',
            'customer_phone' => $sale->customer_phone,
            'items' => $items,
            'subtotal' => (float) $sale->subtotal,
            'discount_amount' => (float) ($sale->discount_amount ?? 0),
            'tax_amount' => (float) ($sale->tax_amount ?? 0),
            'total_amount' => (float) $sale->total_amount,
            'amount_paid' => (float) ($sale->amount_paid ?? $sale->total_amount),
            'change_amount' => (float) ($sale->change_amount ?? 0),
            'payment_method' => $sale->payment_method ?? 'cash',
            'status' => $sale->status,
            'items_count' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
        ];
    }

    /**
     * Get sales statistics for dashboard.
     */
    public function getStats(string $companyId, ?string $branchId = null): array
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        $baseQuery = Sale::where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $todaySales = (clone $baseQuery)->where('created_at', '>=', $today);
        $weekSales = (clone $baseQuery)->where('created_at', '>=', $weekStart);
        $monthSales = (clone $baseQuery)->where('created_at', '>=', $monthStart);

        return [
            'today' => [
                'count' => $todaySales->count(),
                'total' => (float) $todaySales->sum('total_amount'),
                'paid' => (float) $todaySales->sum('amount_paid'),
            ],
            'this_week' => [
                'count' => $weekSales->count(),
                'total' => (float) $weekSales->sum('total_amount'),
                'paid' => (float) $weekSales->sum('amount_paid'),
            ],
            'this_month' => [
                'count' => $monthSales->count(),
                'total' => (float) $monthSales->sum('total_amount'),
                'paid' => (float) $monthSales->sum('amount_paid'),
            ],
            'average_order_value' => (float) $baseQuery->avg('total_amount') ?? 0,
        ];
    }

    /**
     * Record a payment for an existing sale.
     */
    public function addPayment(string $saleId, float $amount, string $paymentMethod): Sale
    {
        return DB::transaction(function () use ($saleId, $amount, $paymentMethod) {
            $sale = Sale::findOrFail($saleId);

            $newPaid = ($sale->amount_paid ?? 0) + $amount;
            $sale->update([
                'amount_paid' => $newPaid,
                'change_amount' => max(0, $newPaid - $sale->total_amount),
                'payment_method' => $paymentMethod,
                'status' => $newPaid >= $sale->total_amount ? 'completed' : 'partial',
            ]);

            return $sale->fresh();
        });
    }

    /**
     * Void/Cancel a sale and restore stock via FEFO.
     *
     * Steps:
     * 1. Validate sale is not already voided
     * 2. Restore stock to original batches via FefoStockService::restore()
     * 3. Log stock movements for each item (restoration)
     * 4. Update sale status to 'voided'
     * 5. Update Cash Register if applicable
     * 6. Dispatch SaleVoided event
     *
     * @throws \RuntimeException if sale is already voided
     */
    public function voidSale(string $saleId, ?string $reason = null): Sale
    {
        return DB::transaction(function () use ($saleId, $reason) {
            $sale = Sale::with(['items', 'items.product'])->findOrFail($saleId);

            if ($sale->status === 'voided') {
                throw new \RuntimeException("Sale {$sale->invoice_number} is already voided.");
            }

            // Restore stock for each item
            foreach ($sale->items as $item) {
                // Check if item has FEFO allocation (inventory_id)
                if ($item->inventory_id) {
                    // Restore to the specific inventory batch
                    $this->fefoStockService->restore(
                        allocations: [[
                            'inventory_id' => $item->inventory_id,
                            'product_id' => $item->product_id,
                            'branch_id' => $sale->branch_id,
                            'batch_number' => $item->batch_number,
                            'quantity' => (float) $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                            'cost_price' => (float) ($item->cost_price ?? 0),
                        ]],
                        referenceType: 'sale_void',
                        referenceId: $sale->id,
                    );
                } else {
                    // Legacy mode: log restore movement
                    $this->stockMovementService->logMovement(
                        productId: $item->product_id,
                        branchId: $sale->branch_id,
                        movementType: 'in',
                        quantity: (float) $item->quantity,
                        referenceType: 'sale_void',
                        referenceId: $sale->id,
                        batchNumber: $item->batch_number,
                        extra: ['restored_from_sale' => $sale->id, 'reason' => $reason]
                    );
                }
            }

            // Update sale status
            $sale->update([
                'status' => 'voided',
                'void_reason' => $reason,
                'voided_at' => now(),
            ]);

            // Update Cash Register if applicable
            if ($sale->cashRegister) {
                $sale->cashRegister->decrement('total_sales', $sale->total_amount);
            }

            // Dispatch event
            event(new SaleVoided($sale, $reason));

            return $sale->fresh(['items.product', 'payments']);
        });
    }

    /**
     * Generate a unique invoice number.
     */
    protected function generateInvoiceNumber(?string $companyId, ?string $branchId = null): string
    {
        $prefix = 'INV-';
        if ($branchId) {
            $prefix = 'INV-' . strtoupper(substr($branchId, 0, 4)) . '-';
        }
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return $prefix . $date . '-' . $random;
    }
}

