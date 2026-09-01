<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Models\Barcode;
use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\Stock\StockAllocationService;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private StockAllocationService $stockAllocationService,
        private FinancialTransactionService $financialTransactionService,
        private SupplierLedgerService $supplierLedgerService,
        private CacheService $cacheService
    ) {}

    /**
     * List purchases with filters.
     */
    public function list(array $filters, int $businessId, int $perPage = 10)
    {
        $cacheKey = 'purchases:list:' . $businessId . ':' . md5(serialize($filters) . $perPage);

        return $this->cacheService->remember($cacheKey, CacheService::TTL_SHORT, function () use ($filters, $businessId, $perPage) {
            return Purchase::select('id', 'party_id', 'branch_id', 'invoiceNumber', 'purchaseDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType', 'status', 'note')
                ->with('party:id,name,phone')
                ->when(!empty($filters['search']), function ($query) use ($filters) {
                    $term = '%' . $filters['search'] . '%';
                    $query->where(function ($subQuery) use ($term) {
                        $subQuery->where('invoiceNumber', 'like', $term)
                            ->orWhere('note', 'like', $term)
                            ->orWhereHas('party', function ($q) use ($term) {
                                $q->where('name', 'like', $term)
                                    ->orWhere('phone', 'like', $term);
                            });
                    });
                })
                ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
                ->when(!empty($filters['party_id']), fn($q) => $q->where('party_id', $filters['party_id']))
                ->when(!empty($filters['branch_id']), fn($q) => $q->where('branch_id', $filters['branch_id']))
                ->when(!empty($filters['from_date']), fn($q) => $q->where('purchaseDate', '>=', $filters['from_date']))
                ->when(!empty($filters['to_date']), fn($q) => $q->where('purchaseDate', '<=', $filters['to_date']))
                ->withCount('purchaseReturns')
                ->where('business_id', $businessId)
                ->latest()
                ->paginate($perPage);
        });
    }

    /**
     * Create a purchase invoice with full atomic transaction.
     *
     * Operations in order:
     * 1. Validate supplier exists
     * 2. Recalculate financials server-side
     * 3. Create purchase record
     * 4. Create purchase line items
     * 5. Update inventory (stock)
     * 6. Record stock movements
     * 7. Update supplier balance via ledger
     * 8. Record financial transaction
     * 9. Audit log entry
     */
    public function create(array $data, int $businessId, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $businessId, $userId) {
            // 1. Validate supplier
            if (empty($data['party_id'])) {
                throw new BusinessRuleException(
                    ErrorCode::VALIDATION_ERROR,
                    __('A supplier must be selected for purchase invoices.'),
                    []
                );
            }
            $party = Party::where('id', $data['party_id'])
                ->where('business_id', $businessId)
                ->firstOrFail();

            // 2. Validate items exist
            if (empty($data['products']) || count($data['products']) === 0) {
                throw new BusinessRuleException(
                    ErrorCode::VALIDATION_ERROR,
                    __('At least one item is required.'),
                    []
                );
            }

            // 3. Recalculate financials server-side (never trust frontend totals)
            $financials = $this->calculateFinancials($data, $data['discountAmount'] ?? 0, $data['tax_amount'] ?? 0);

            // 4. Create purchase record
            $purchase = Purchase::create([
                'party_id' => $party->id,
                'business_id' => $businessId,
                'branch_id' => $data['branch_id'] ?? null,
                'user_id' => $userId,
                'tax_id' => $data['tax_id'] ?? null,
                'discountAmount' => $financials['discountAmount'],
                'tax_amount' => $financials['taxAmount'],
                'dueAmount' => $financials['dueAmount'],
                'paidAmount' => $financials['paidAmount'],
                'totalAmount' => $financials['totalAmount'],
                'isPaid' => $financials['dueAmount'] <= 0,
                'paymentType' => $data['paymentType'] ?? 'Cash',
                'purchaseDate' => $data['purchaseDate'] ?? now(),
                'note' => $data['note'] ?? null,
                'status' => 'received',
                'received_at' => now(),
            ]);

            // 5. Create purchase line items
            $purchaseDetails = [];
            foreach ($data['products'] as $productData) {
                $purchaseDetails[] = [
                    'purchase_id' => $purchase->id,
                    'product_id' => $productData['product_id'],
                    'batch_no' => $productData['batch_no'] ?? null,
                    'expire_date' => $productData['expire_date'] ?? null,
                    'quantities' => $productData['quantities'] ?? 0,
                    'purchase_without_tax' => $productData['purchase_without_tax'] ?? 0,
                    'purchase_with_tax' => $productData['purchase_with_tax'] ?? 0,
                    'profit_percent' => $productData['profit_percent'] ?? 0,
                    'sales_price' => $productData['sales_price'] ?? 0,
                    'wholesale_price' => $productData['wholesale_price'] ?? 0,
                ];
            }
            PurchaseDetails::insert($purchaseDetails);

            // 6. Update inventory and record stock movements
            $this->updateInventoryForPurchase($purchase, $data['products'], $businessId, $userId);

            // 7. Update supplier balance via ledger
            $this->supplierLedgerService->recordPurchase($purchase, $userId);

            // Also update Party.due for backward compatibility
            if ($financials['dueAmount'] > 0) {
                $party->update(['due' => $party->due + $financials['dueAmount']]);
            }

            // 8. Record financial transaction
            $this->financialTransactionService->createFromPurchase($purchase->id, $businessId);

            // 9. Audit log
            AuditLogger::log('create_purchase', "Created purchase invoice #{$purchase->invoiceNumber}", [
                'purchase_id' => $purchase->id,
                'invoice_number' => $purchase->invoiceNumber,
                'supplier' => $party->name,
                'total' => $purchase->totalAmount,
                'paid' => $purchase->paidAmount,
                'remaining' => $purchase->dueAmount,
            ]);

            // Invalidate cached purchase list for this business
            $this->cacheService->forget('purchases:list:' . $businessId . ':' . md5(serialize([]) . 10));

            return $purchase->load([
                'tax:id,name,rate',
                'party:id,name,phone',
                'details.product:id,productName,productCode',
                'details:id,purchase_id,product_id,purchase_with_tax,quantities,batch_no',
            ]);
        });
    }

    /**
     * Show a purchase with all relations.
     */
    public function show(int $id)
    {
        return Purchase::with([
            'tax:id,name,rate',
            'user:id,name',
            'party:id,name,phone,due',
            'branch:id,branch_name',
            'purchaseReturns:id,purchase_id,invoice_no,return_date,total_amount,credit_amount,status',
            'purchaseReturns.details:id,purchase_return_id,product_id,return_qty,return_amount,unit_price',
            'details.product:id,productName,productCode,tax_type',
            'details:id,purchase_id,product_id,purchase_with_tax,quantities,batch_no,purchase_without_tax,profit_percent,sales_price,wholesale_price',
        ])->findOrFail($id);
    }

    /**
     * Update a purchase invoice.
     */
    public function update(Purchase $purchase, array $data, int $businessId, int $userId)
    {
        return DB::transaction(function () use ($purchase, $data, $businessId, $userId) {
            // Revert previous stock (allocate = deduct from inventory, since purchase added stock)
            $prevDetails = PurchaseDetails::where('purchase_id', $purchase->id)->get();
            foreach ($prevDetails as $prevDetail) {
                $stock = $this->resolveStock($prevDetail->product_id, $prevDetail->batch_no, $businessId, $data['branch_id'] ?? null);
                if ($stock) {
                    $this->stockAllocationService->allocate(
                        $stock, $prevDetail->quantities, Purchase::class, $purchase->id, $userId,
                        'Reverting previous purchase quantities for update'
                    );
                }
            }

            // Delete old details
            PurchaseDetails::where('purchase_id', $purchase->id)->delete();

            // Recalculate financials
            $financials = $this->calculateFinancials($data, $data['discountAmount'] ?? 0, $data['tax_amount'] ?? 0);

            // Create new details
            $purchaseDetails = [];
            foreach ($data['products'] as $productData) {
                $purchaseDetails[] = [
                    'purchase_id' => $purchase->id,
                    'product_id' => $productData['product_id'],
                    'batch_no' => $productData['batch_no'] ?? null,
                    'expire_date' => $productData['expire_date'] ?? null,
                    'quantities' => $productData['quantities'] ?? 0,
                    'purchase_without_tax' => $productData['purchase_without_tax'] ?? 0,
                    'purchase_with_tax' => $productData['purchase_with_tax'] ?? 0,
                    'profit_percent' => $productData['profit_percent'] ?? 0,
                    'sales_price' => $productData['sales_price'] ?? 0,
                    'wholesale_price' => $productData['wholesale_price'] ?? 0,
                ];
            }
            PurchaseDetails::insert($purchaseDetails);

            // Update inventory
            $this->updateInventoryForPurchase($purchase, $data['products'], $businessId, $userId);

            // Update purchase record
            $purchase->update([
                'party_id' => $data['party_id'] ?? $purchase->party_id,
                'branch_id' => $data['branch_id'] ?? $purchase->branch_id,
                'discountAmount' => $financials['discountAmount'],
                'tax_amount' => $financials['taxAmount'],
                'dueAmount' => $financials['dueAmount'],
                'paidAmount' => $financials['paidAmount'],
                'totalAmount' => $financials['totalAmount'],
                'isPaid' => $financials['dueAmount'] <= 0,
                'paymentType' => $data['paymentType'] ?? $purchase->paymentType,
                'purchaseDate' => $data['purchaseDate'] ?? $purchase->purchaseDate,
                'note' => $data['note'] ?? $purchase->note,
                'user_id' => $userId,
            ]);

            // Update supplier due
            $party = Party::find($purchase->party_id);
            if ($party) {
                $party->update(['due' => $party->due - $purchase->dueAmount + $financials['dueAmount']]);
            }

            // Update financial transaction
            $this->financialTransactionService->deleteTransactionFor($purchase);
            $this->financialTransactionService->createFromPurchase($purchase->id, $businessId);

            // Invalidate cached purchase list for this business
            $this->cacheService->forget('purchases:list:' . $businessId . ':' . md5(serialize([]) . 10));

            return $purchase;
        });
    }

    /**
     * Delete/cancel a purchase invoice with full reversal.
     */
    public function delete(Purchase $purchase, int $businessId, int $userId)
    {
        return DB::transaction(function () use ($purchase, $businessId, $userId) {
            // Reverse stock (allocate = deduct from inventory, since purchase added stock)
            $purchaseDetails = PurchaseDetails::where('purchase_id', $purchase->id)->get();
            foreach ($purchaseDetails as $detail) {
                $stock = $this->resolveStock($detail->product_id, $detail->batch_no, $businessId, $purchase->branch_id);
                if ($stock) {
                    if ($stock->productStock < $detail->quantities) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_BATCH_QUANTITY_MISMATCH,
                            __('Insufficient stock to reverse purchase. Batch: :batch, Available: :available, Required: :required', [
                                'batch' => $detail->batch_no ?? 'N/A',
                                'available' => $stock->productStock,
                                'required' => $detail->quantities,
                            ]),
                            ['batch_no' => $detail->batch_no, 'available' => $stock->productStock, 'required' => $detail->quantities]
                        );
                    }
                    $this->stockAllocationService->allocate(
                        $stock, $detail->quantities, Purchase::class, $purchase->id, $userId,
                        'Reversing purchase due to cancellation'
                    );
                }
            }

            // Reverse supplier balance via ledger
            if ($purchase->party_id) {
                $this->supplierLedgerService->reversePurchase($purchase, $userId);
                $party = Party::find($purchase->party_id);
                if ($party) {
                    $party->update(['due' => max(0, $party->due - $purchase->dueAmount)]);
                }
            }

            // Reverse business balance
            $business = Business::findOrFail($businessId);
            if ($purchase->paidAmount > 0) {
                $business->update(['remainingShopBalance' => $business->remainingShopBalance + $purchase->paidAmount]);
            }

            // Delete financial transaction
            $this->financialTransactionService->deleteTransactionFor($purchase);

            // Mark as cancelled
            $purchase->update(['status' => 'canceled', 'canceled_at' => now()]);

            // Audit log
            AuditLogger::log('cancel_purchase', "Cancelled purchase invoice #{$purchase->invoiceNumber}", [
                'purchase_id' => $purchase->id,
                'invoice_number' => $purchase->invoiceNumber,
            ]);

            // Invalidate cached purchase list for this business
            $this->cacheService->forget('purchases:list:' . $businessId . ':' . md5(serialize([]) . 10));

            return true;
        });
    }

    /**
     * Search for a product by barcode and return it ready for purchase line item.
     */
    public function searchByBarcode(string $barcode, int $businessId): ?array
    {
        // Try barcode table first
        $barcodeRecord = Barcode::where('barcode_number', $barcode)
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->with('product:id,productName,productCode,sales_price,purchase_without_tax,profit_percent')
            ->first();

        if ($barcodeRecord && $barcodeRecord->product) {
            return [
                'product_id' => $barcodeRecord->product_id,
                'barcode' => $barcodeRecord->barcode_number,
                'product_name' => $barcodeRecord->product->productName,
                'product_code' => $barcodeRecord->product->productCode,
                'purchase_price' => $barcodeRecord->product->purchase_without_tax,
                'sales_price' => $barcodeRecord->product->sales_price,
                'profit_percent' => $barcodeRecord->product->profit_percent,
            ];
        }

        // Fallback: search by productCode
        $product = Product::where('productCode', $barcode)
            ->where('business_id', $businessId)
            ->first();

        if ($product) {
            return [
                'product_id' => $product->id,
                'barcode' => $barcode,
                'product_name' => $product->productName,
                'product_code' => $product->productCode,
                'purchase_price' => $product->purchase_without_tax,
                'sales_price' => $product->sales_price,
                'profit_percent' => $product->profit_percent,
            ];
        }

        return null;
    }

    /**
     * Search for products by name or code.
     */
    public function searchProducts(string $query, int $businessId, int $limit = 10)
    {
        return Product::where('business_id', $businessId)
            ->where(function ($q) use ($query) {
                $term = '%' . $query . '%';
                $q->where('productName', 'like', $term)
                    ->orWhere('productCode', 'like', $term);
            })
            ->select('id', 'productName', 'productCode', 'purchase_without_tax', 'sales_price', 'profit_percent', 'alert_qty')
            ->limit($limit)
            ->get();
    }

    /**
     * Get purchase summary statistics.
     */
    public function getStatistics(int $businessId): array
    {
        $purchases = Purchase::where('business_id', $businessId);

        return [
            'total_purchases' => (clone $purchases)->count(),
            'total_amount' => (clone $purchases)->sum('totalAmount'),
            'total_paid' => (clone $purchases)->sum('paidAmount'),
            'total_due' => (clone $purchases)->sum('dueAmount'),
            'this_month' => (clone $purchases)->whereMonth('purchaseDate', now()->month)->sum('totalAmount'),
            'by_status' => (clone $purchases)->selectRaw('status, count(*) as count, sum(totalAmount) as total')
                ->groupBy('status')
                ->get()
                ->keyBy('status'),
        ];
    }

    /**
     * Calculate all financial values server-side from line items.
     * NEVER trust frontend-calculated totals.
     */
    private function calculateFinancials(array $data, float $discountAmount, float $taxAmount): array
    {
        $products = $data['products'] ?? [];
        $subtotal = 0;
        foreach ($products as $item) {
            $qty = (int) ($item['quantities'] ?? 0);
            $price = (float) ($item['purchase_with_tax'] ?? $item['purchase_without_tax'] ?? 0);
            $lineTotal = $qty * $price;
            $subtotal += $lineTotal;
        }

        $totalAmount = $subtotal - $discountAmount + $taxAmount;
        $totalAmount = max(0, $totalAmount); // Never negative

        $paidAmount = min((float) ($data['paidAmount'] ?? 0), $totalAmount);

        return [
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'taxAmount' => $taxAmount,
            'totalAmount' => $totalAmount,
            'paidAmount' => $paidAmount,
            'dueAmount' => max(0, $totalAmount - $paidAmount),
        ];
    }

    /**
     * Update inventory for a purchase.
     */
    private function updateInventoryForPurchase(Purchase $purchase, array $products, int $businessId, int $userId): void
    {
        foreach ($products as $productData) {
            $productId = $productData['product_id'];
            $quantity = (int) ($productData['quantities'] ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            // Update product pricing
            Product::where('id', $productId)->update([
                'sales_price' => $productData['sales_price'] ?? 0,
                'profit_percent' => $productData['profit_percent'] ?? 0,
                'wholesale_price' => $productData['wholesale_price'] ?? 0,
                'purchase_with_tax' => $productData['purchase_with_tax'] ?? 0,
                'purchase_without_tax' => $productData['purchase_without_tax'] ?? 0,
            ]);

            // Resolve stock
            $stock = $this->resolveStock($productId, $productData['batch_no'] ?? null, $businessId, $purchase->branch_id);

            if ($stock) {
                // Update batch info if provided
                if (!empty($productData['batch_no']) || !empty($productData['expire_date'])) {
                    $stock->update([
                        'batch_no' => $productData['batch_no'] ?? $stock->batch_no,
                        'expire_date' => $productData['expire_date'] ?? $stock->expire_date,
                    ]);
                }
                $this->stockAllocationService->addStock(
                    $stock, $quantity, Purchase::class, $purchase->id, $userId,
                    "Stock added from purchase #{$purchase->invoiceNumber}"
                );
            } else {
                // Create new stock entry
                $newStock = Stock::create([
                    'business_id' => $businessId,
                    'branch_id' => $purchase->branch_id,
                    'product_id' => $productId,
                    'batch_no' => $productData['batch_no'] ?? null,
                    'expire_date' => $productData['expire_date'] ?? null,
                    'productStock' => 0,
                ]);
                $this->stockAllocationService->addStock(
                    $newStock, $quantity, Purchase::class, $purchase->id, $userId,
                    "Initial stock from purchase #{$purchase->invoiceNumber}"
                );
            }
        }
    }

    /**
     * Resolve a stock record by product, batch, and branch.
     */
    private function resolveStock(int $productId, ?string $batchNo, int $businessId, ?int $branchId): ?Stock
    {
        $query = Stock::where('product_id', $productId)
            ->where('business_id', $businessId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($batchNo) {
            $stock = $query->where('batch_no', $batchNo)->first();
            if ($stock) {
                return $stock;
            }
        }

        // Fallback: any stock for this product
        return $query->first();
    }
}
