<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Services\Stock\StockAllocationService;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function __construct(
        private StockAllocationService $stockAllocationService,
        private SupplierLedgerService $supplierLedgerService,
        private FinancialTransactionService $financialTransactionService,
    ) {}

    /**
     * List purchase returns with filters.
     */
    public function list(array $filters, int $businessId, int $perPage = 10)
    {
        return PurchaseReturn::select('id', 'business_id', 'purchase_id', 'party_id', 'invoice_no', 'return_date', 'total_amount', 'credit_amount', 'status', 'reason')
            ->with([
                'purchase:id,invoiceNumber,party_id',
                'purchase.party:id,name',
                'details',
            ])
            ->where('business_id', $businessId)
            ->when(!empty($filters['purchase_id']), fn($q) => $q->where('purchase_id', $filters['purchase_id']))
            ->when(!empty($filters['party_id']), fn($q) => $q->where('party_id', $filters['party_id']))
            ->when(!empty($filters['from_date']), fn($q) => $q->where('return_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']), fn($q) => $q->where('return_date', '<=', $filters['to_date']))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Process a purchase return with full atomic transaction.
     */
    public function processReturn(Purchase $purchase, array $returnData, int $businessId, int $userId): PurchaseReturn
    {
        return DB::transaction(function () use ($purchase, $returnData, $businessId, $userId) {
            // 1. Validate return eligibility
            $this->validateReturnEligibility($purchase, $returnData);

            // 2. Calculate total credit amount from line items
            $totalCredit = 0;
            $returnDetails = [];

            foreach ($returnData['items'] as $itemData) {
                $purchaseDetail = PurchaseDetails::findOrFail($itemData['purchase_detail_id']);

                // Validate returnable quantity
                $returnableQty = $this->getReturnableQuantity($purchaseDetail);
                $returnQty = (int) $itemData['return_qty'];

                if ($returnQty <= 0) {
                    throw new BusinessRuleException(
                        ErrorCode::VALIDATION_ERROR,
                        __('Return quantity must be greater than 0.'),
                        ['detail_id' => $purchaseDetail->id]
                    );
                }

                if ($returnQty > $returnableQty) {
                    throw new BusinessRuleException(
                        ErrorCode::VALIDATION_ERROR,
                        __('Cannot return :return_qty items. Only :returnable items are returnable for :product.', [
                            'return_qty' => $returnQty,
                            'returnable' => $returnableQty,
                            'product' => $purchaseDetail->product->productName ?? 'item',
                        ]),
                        ['detail_id' => $purchaseDetail->id, 'returnable' => $returnableQty, 'requested' => $returnQty]
                    );
                }

                // Calculate credit for this line
                $unitPrice = (float) $purchaseDetail->purchase_without_tax;
                $discount = (float) ($itemData['discount'] ?? 0);
                $lineCredit = ($unitPrice * $returnQty) - $discount;
                $totalCredit += $lineCredit;

                $returnDetails[] = [
                    'purchase_detail_id' => $purchaseDetail->id,
                    'product_id' => $purchaseDetail->product_id,
                    'return_qty' => $returnQty,
                    'return_amount' => $lineCredit,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'tax' => (float) ($itemData['tax'] ?? 0),
                    'credit_amount' => $lineCredit,
                    'reason' => $itemData['reason'] ?? null,
                    'batch_no' => $purchaseDetail->batch_no,
                ];
            }

            // 3. Create purchase return record
            $purchaseReturn = PurchaseReturn::create([
                'business_id' => $businessId,
                'purchase_id' => $purchase->id,
                'party_id' => $purchase->party_id,
                'user_id' => $userId,
                'return_date' => $returnData['return_date'] ?? now(),
                'total_amount' => $totalCredit,
                'credit_amount' => $totalCredit,
                'status' => 'completed',
                'reason' => $returnData['reason'] ?? null,
                'notes' => $returnData['notes'] ?? null,
            ]);

            // 4. Create return details and deduct stock
            foreach ($returnDetails as $detailData) {
                $detailData['business_id'] = $businessId;
                $detailData['purchase_return_id'] = $purchaseReturn->id;
                PurchaseReturnDetail::create($detailData);

                // Deduct stock
                $purchaseDetail = PurchaseDetails::find($detailData['purchase_detail_id']);
                $stock = $this->resolveStock(
                    $detailData['product_id'],
                    $detailData['batch_no'],
                    $businessId,
                    $purchase->branch_id
                );

                if ($stock) {
                    if ($stock->productStock < $detailData['return_qty']) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                            __('Insufficient stock for return. Available: :available, Return: :return', [
                                'available' => $stock->productStock,
                                'return' => $detailData['return_qty'],
                            ]),
                            ['stock_id' => $stock->id, 'available' => $stock->productStock, 'requested' => $detailData['return_qty']]
                        );
                    }
                    $this->stockAllocationService->allocate(
                        $stock,
                        $detailData['return_qty'],
                        PurchaseReturn::class,
                        $purchaseReturn->id,
                        $userId,
                        "Stock deducted for purchase return #{$purchaseReturn->invoice_no}"
                    );
                }

                // NOTE: We do NOT decrement purchase_details.quantities here.
                // getReturnableQuantity() calculates remaining = original - sum(returned).
                // Decrementing would cause double-counting on subsequent returns.
            }

            // 5. Record supplier ledger entry (credit = return value)
            $this->supplierLedgerService->recordPurchaseReturn($purchaseReturn, $totalCredit, $userId);

            // 6. Update supplier balance
            if ($purchase->party_id) {
                $party = Party::find($purchase->party_id);
                if ($party) {
                    $party->update(['due' => max(0, $party->due - $totalCredit)]);
                }
            }

            // 7. Update parent purchase totals
            $purchase->update([
                'totalAmount' => max(0, $purchase->totalAmount - $totalCredit),
                'dueAmount' => max(0, $purchase->dueAmount - $totalCredit),
            ]);

            // 8. Check if fully returned
            $totalPurchased = PurchaseDetails::where('purchase_id', $purchase->id)->sum('quantities');
            $totalReturned = PurchaseReturnDetail::whereHas('purchaseReturn', fn($q) => $q->where('purchase_id', $purchase->id))
                ->sum('return_qty');
            if ($totalReturned >= $totalPurchased) {
                $purchase->update(['status' => 'returned_fully']);
            } elseif ($totalReturned > 0) {
                $purchase->update(['status' => 'returned_partially']);
            }

            // 9. Audit log
            AuditLogger::log('create_purchase_return', "Created purchase return #{$purchaseReturn->invoice_no} for purchase #{$purchase->invoiceNumber}", [
                'purchase_return_id' => $purchaseReturn->id,
                'purchase_id' => $purchase->id,
                'credit_amount' => $totalCredit,
                'items_count' => count($returnDetails),
            ]);

            return $purchaseReturn->fresh(['details.product', 'purchase.party']);
        });
    }

    /**
     * Get the returnable quantity for a purchase detail line.
     * Returnable = original purchased - previously returned.
     */
    public function getReturnableQuantity(PurchaseDetails $purchaseDetail): int
    {
        $purchased = $purchaseDetail->quantities;
        $returned = PurchaseReturnDetail::where('purchase_detail_id', $purchaseDetail->id)
            ->sum('return_qty');

        return max(0, $purchased - $returned);
    }

    /**
     * Get returnable quantities for all items in a purchase.
     */
    public function getReturnableItems(int $purchaseId): array
    {
        $details = PurchaseDetails::with('product:id,productName')
            ->where('purchase_id', $purchaseId)
            ->get();

        return $details->map(function ($detail) {
            $returned = PurchaseReturnDetail::where('purchase_detail_id', $detail->id)->sum('return_qty');
            return [
                'purchase_detail_id' => $detail->id,
                'product_id' => $detail->product_id,
                'product_name' => $detail->product->productName ?? 'Unknown',
                'batch_no' => $detail->batch_no,
                'purchased_qty' => $detail->quantities,
                'returned_qty' => $returned,
                'returnable_qty' => max(0, $detail->quantities - $returned),
                'unit_price' => $detail->purchase_without_tax,
                'total_value' => $detail->purchase_without_tax * $detail->quantities,
            ];
        })->toArray();
    }

    /**
     * Show a purchase return with all relations.
     */
    public function show(int $id)
    {
        return PurchaseReturn::with([
            'purchase:id,invoiceNumber,party_id,totalAmount',
            'purchase.party:id,name,phone',
            'details.product:id,productName',
            'user:id,name',
        ])->findOrFail($id);
    }

    /**
     * Validate return eligibility.
     */
    private function validateReturnEligibility(Purchase $purchase, array $returnData): void
    {
        if ($purchase->status === 'canceled') {
            throw new BusinessRuleException(
                ErrorCode::VALIDATION_ERROR,
                __('Cannot return items from a cancelled purchase.'),
                ['purchase_id' => $purchase->id]
            );
        }

        if (empty($returnData['items'])) {
            throw new BusinessRuleException(
                ErrorCode::VALIDATION_ERROR,
                __('At least one item must be selected for return.'),
                []
            );
        }
    }

    /**
     * Resolve stock by product, batch, and branch.
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

        return $query->first();
    }
}
