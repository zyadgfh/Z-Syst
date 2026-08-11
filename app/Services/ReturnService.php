<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetails;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Models\Stock;
use App\Models\FinancialTransaction;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;

class ReturnService
{
    use WithTransactionalOperations;

    /**
     * Process a sale return.
     *
     * @param Sale $sale
     * @param array<string, mixed> $returnData
     * @param int $businessId
     * @param int $userId
     * @return SaleReturn
     * @throws \Exception
     */
    public function processSaleReturn(Sale $sale, array $returnData, int $businessId, int $userId): SaleReturn
    {
        return $this->executeTransaction(function () use ($sale, $returnData, $businessId, $userId) {
            // Validate return eligibility
            $this->validateSaleReturnEligibility($sale, $returnData);

            // Create sale return record
            $saleReturn = SaleReturn::create([
                'business_id' => $businessId,
                'sale_id' => $sale->id,
                'party_id' => $sale->party_id,
                'return_date' => $returnData['return_date'] ?? now(),
                'reason' => $returnData['reason'] ?? 'Customer request',
                'total_amount' => 0, // Will be calculated
                'refund_amount' => 0, // Will be calculated
                'status' => 'pending',
                'notes' => $returnData['notes'] ?? null,
                'processed_by' => $userId,
            ]);

            $totalRefund = 0;

            // Process return items
            foreach ($returnData['items'] as $itemData) {
                $saleDetail = $sale->details()->findOrFail($itemData['sale_detail_id']);
                $returnQuantity = $itemData['quantity'];

                if ($returnQuantity > $saleDetail->quantities) {
                    throw new \Exception("Cannot return more than sold. Item: {$saleDetail->id}, Sold: {$saleDetail->quantities}, Return: {$returnQuantity}");
                }

                // Calculate refund amount
                $refundAmount = ($saleDetail->price * $returnQuantity) - ($itemData['discount'] ?? 0);
                $totalRefund += $refundAmount;

                // Create return detail
                SaleReturnDetails::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_detail_id' => $saleDetail->id,
                    'product_id' => $saleDetail->product_id,
                    'quantity' => $returnQuantity,
                    'unit_price' => $saleDetail->price,
                    'discount' => $itemData['discount'] ?? 0,
                    'tax' => $itemData['tax'] ?? 0,
                    'refund_amount' => $refundAmount,
                    'reason' => $itemData['reason'] ?? null,
                    'batch_no' => $saleDetail->batch_no,
                ]);

                // Restore stock
                $this->restoreStockForSaleReturn($saleDetail, $returnQuantity, $businessId);
            }

            // Update sale return totals
            $saleReturn->update([
                'total_amount' => $totalRefund,
                'refund_amount' => $totalRefund,
            ]);

            // Update party due if refund is pending
            if ($sale->party_id && $returnData['refund_type'] === 'credit') {
                $party = $sale->party;
                $party->update([
                    'due' => $party->due - $totalRefund,
                ]);
            }

            // Create financial transaction for refund
            $this->createReturnFinancialTransaction($saleReturn, $businessId, $userId);

            return $saleReturn->fresh(['details.product', 'sale.party']);
        });
    }

    /**
     * Process a purchase return.
     *
     * @param Purchase $purchase
     * @param array<string, mixed> $returnData
     * @param int $businessId
     * @param int $userId
     * @return PurchaseReturn
     * @throws \Exception
     */
    public function processPurchaseReturn(Purchase $purchase, array $returnData, int $businessId, int $userId): PurchaseReturn
    {
        return $this->executeTransaction(function () use ($purchase, $returnData, $businessId, $userId) {
            // Validate return eligibility
            $this->validatePurchaseReturnEligibility($purchase, $returnData);

            // Create purchase return record
            $purchaseReturn = PurchaseReturn::create([
                'business_id' => $businessId,
                'purchase_id' => $purchase->id,
                'party_id' => $purchase->party_id,
                'return_date' => $returnData['return_date'] ?? now(),
                'reason' => $returnData['reason'] ?? 'Supplier return',
                'total_amount' => 0, // Will be calculated
                'credit_amount' => 0, // Will be calculated
                'status' => 'pending',
                'notes' => $returnData['notes'] ?? null,
                'processed_by' => $userId,
            ]);

            $totalCredit = 0;

            // Process return items
            foreach ($returnData['items'] as $itemData) {
                $purchaseDetail = $purchase->details()->findOrFail($itemData['purchase_detail_id']);
                $returnQuantity = $itemData['quantity'];

                if ($returnQuantity > $purchaseDetail->quantities) {
                    throw new \Exception("Cannot return more than purchased. Item: {$purchaseDetail->id}, Purchased: {$purchaseDetail->quantities}, Return: {$returnQuantity}");
                }

                // Calculate credit amount
                $creditAmount = ($purchaseDetail->purchase_without_tax * $returnQuantity) - ($itemData['discount'] ?? 0);
                $totalCredit += $creditAmount;

                // Create return detail
                PurchaseReturnDetail::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_detail_id' => $purchaseDetail->id,
                    'product_id' => $purchaseDetail->product_id,
                    'quantity' => $returnQuantity,
                    'unit_price' => $purchaseDetail->purchase_without_tax,
                    'discount' => $itemData['discount'] ?? 0,
                    'tax' => $itemData['tax'] ?? 0,
                    'credit_amount' => $creditAmount,
                    'reason' => $itemData['reason'] ?? null,
                    'batch_no' => $purchaseDetail->batch_no,
                ]);

                // Deduct stock
                $this->deductStockForPurchaseReturn($purchaseDetail, $returnQuantity, $businessId);
            }

            // Update purchase return totals
            $purchaseReturn->update([
                'total_amount' => $totalCredit,
                'credit_amount' => $totalCredit,
            ]);

            // Update party due if credit is pending
            if ($purchase->party_id && $returnData['credit_type'] === 'credit') {
                $party = $purchase->party;
                $party->update([
                    'due' => $party->due - $totalCredit,
                ]);
            }

            // Create financial transaction for credit
            $this->createPurchaseReturnFinancialTransaction($purchaseReturn, $businessId, $userId);

            return $purchaseReturn->fresh(['details.product', 'purchase.party']);
        });
    }

    /**
     * Validate sale return eligibility.
     *
     * @param Sale $sale
     * @param array<string, mixed> $returnData
     * @return void
     * @throws \Exception
     */
    protected function validateSaleReturnEligibility(Sale $sale, array $returnData): void
    {
        // Check if sale is too old for return (e.g., 30 days)
        $saleDate = \Carbon\Carbon::parse($sale->saleDate ?? $sale->created_at);
        $returnPeriod = $returnData['return_period_days'] ?? 30;

        if ($saleDate->diffInDays(now()) > $returnPeriod) {
            throw new \Exception("Sale is too old for return. Sale date: {$saleDate->toDateString()}, Return period: {$returnPeriod} days");
        }

        // Check if there are already returns for this sale
        $existingReturns = SaleReturn::where('sale_id', $sale->id)->count();
        if ($existingReturns > 0 && !($returnData['allow_multiple_returns'] ?? false)) {
            throw new \Exception('This sale already has a return record');
        }
    }

    /**
     * Validate purchase return eligibility.
     *
     * @param Purchase $purchase
     * @param array<string, mixed> $returnData
     * @return void
     * @throws \Exception
     */
    protected function validatePurchaseReturnEligibility(Purchase $purchase, array $returnData): void
    {
        // Check if purchase is too old for return
        $purchaseDate = \Carbon\Carbon::parse($purchase->purchaseDate ?? $purchase->created_at);
        $returnPeriod = $returnData['return_period_days'] ?? 30;

        if ($purchaseDate->diffInDays(now()) > $returnPeriod) {
            throw new \Exception("Purchase is too old for return. Purchase date: {$purchaseDate->toDateString()}, Return period: {$returnPeriod} days");
        }

        // Check if there are already returns for this purchase
        $existingReturns = PurchaseReturn::where('purchase_id', $purchase->id)->count();
        if ($existingReturns > 0 && !($returnData['allow_multiple_returns'] ?? false)) {
            throw new \Exception('This purchase already has a return record');
        }
    }

    /**
     * Restore stock for sale return.
     *
     * @param SaleDetails $saleDetail
     * @param int $quantity
     * @param int $businessId
     * @return void
     */
    protected function restoreStockForSaleReturn(SaleDetails $saleDetail, int $quantity, int $businessId): void
    {
        $stock = Stock::where('product_id', $saleDetail->product_id)
            ->where('business_id', $businessId)
            ->where('batch_no', $saleDetail->batch_no)
            ->first();

        if ($stock) {
            $stock->increment('productStock', $quantity);
        } else {
            // Create new stock entry if original batch doesn't exist
            Stock::create([
                'business_id' => $businessId,
                'product_id' => $saleDetail->product_id,
                'batch_no' => $saleDetail->batch_no,
                'expire_date' => $saleDetail->expire_date,
                'productStock' => $quantity,
            ]);
        }
    }

    /**
     * Deduct stock for purchase return.
     *
     * @param PurchaseDetails $purchaseDetail
     * @param int $quantity
     * @param int $businessId
     * @return void
     */
    protected function deductStockForPurchaseReturn(PurchaseDetails $purchaseDetail, int $quantity, int $businessId): void
    {
        $stock = Stock::where('product_id', $purchaseDetail->product_id)
            ->where('business_id', $businessId)
            ->where('batch_no', $purchaseDetail->batch_no)
            ->first();

        if ($stock) {
            $newQuantity = $stock->productStock - $quantity;
            if ($newQuantity < 0) {
                throw new \Exception("Insufficient stock for return. Available: {$stock->productStock}, Return: {$quantity}");
            }
            $stock->update(['productStock' => $newQuantity]);
        }
    }

    /**
     * Create financial transaction for sale return.
     *
     * @param SaleReturn $saleReturn
     * @param int $businessId
     * @param int $userId
     * @return void
     */
    protected function createReturnFinancialTransaction(SaleReturn $saleReturn, int $businessId, int $userId): void
    {
        FinancialTransaction::create([
            'business_id' => $businessId,
            'branch_id' => $saleReturn->sale->branch_id ?? null,
            'type' => 'expense', // Refund is an expense
            'amount' => $saleReturn->refund_amount,
            'reference_type' => 'sale_return',
            'reference_id' => $saleReturn->id,
            'description' => 'Sale return refund for sale #' . $saleReturn->sale->invoiceNumber,
            'transaction_date' => $saleReturn->return_date,
            'category' => 'refunds',
            'user_id' => $userId,
            'notes' => 'Return reason: ' . $saleReturn->reason,
        ]);
    }

    /**
     * Create financial transaction for purchase return.
     *
     * @param PurchaseReturn $purchaseReturn
     * @param int $businessId
     * @param int $userId
     * @return void
     */
    protected function createPurchaseReturnFinancialTransaction(PurchaseReturn $purchaseReturn, int $businessId, int $userId): void
    {
        FinancialTransaction::create([
            'business_id' => $businessId,
            'branch_id' => $purchaseReturn->purchase->branch_id ?? null,
            'type' => 'revenue', // Credit is revenue
            'amount' => $purchaseReturn->credit_amount,
            'reference_type' => 'purchase_return',
            'reference_id' => $purchaseReturn->id,
            'description' => 'Purchase return credit for purchase #' . $purchaseReturn->purchase->invoiceNumber,
            'transaction_date' => $purchaseReturn->return_date,
            'category' => 'purchase_returns',
            'user_id' => $userId,
            'notes' => 'Return reason: ' . $purchaseReturn->reason,
        ]);
    }

    /**
     * Calculate return refund amount.
     *
     * @param array $returnItems
     * @return float
     */
    public function calculateReturnRefund(array $returnItems): float
    {
        $totalRefund = 0;

        foreach ($returnItems as $item) {
            $refundAmount = ($item['unit_price'] * $item['quantity']) - ($item['discount'] ?? 0);
            $totalRefund += $refundAmount;
        }

        return $totalRefund;
    }

    /**
     * Validate return rules.
     *
     * @param array<string, mixed> $returnData
     * @param array<string, mixed> $rules
     * @return array
     */
    public function validateReturnRules(array $returnData, array $rules = []): array
    {
        $validationErrors = [];

        // Default rules
        $defaultRules = [
            'max_return_quantity' => null, // No limit by default
            'max_return_percentage' => 100, // 100% by default
            'require_reason' => true,
            'min_return_days' => 1,
            'max_return_days' => 30,
        ];

        $rules = array_merge($defaultRules, $rules);

        // Validate return period
        if (isset($returnData['original_date'])) {
            $originalDate = \Carbon\Carbon::parse($returnData['original_date']);
            $daysSinceOriginal = $originalDate->diffInDays(now());

            if ($daysSinceOriginal < $rules['min_return_days']) {
                $validationErrors[] = "Return period not met. Minimum: {$rules['min_return_days']} days";
            }

            if ($daysSinceOriginal > $rules['max_return_days']) {
                $validationErrors[] = "Return period exceeded. Maximum: {$rules['max_return_days']} days";
            }
        }

        // Validate return quantity
        foreach ($returnData['items'] ?? [] as $item) {
            if ($rules['max_return_quantity'] && $item['quantity'] > $rules['max_return_quantity']) {
                $validationErrors[] = "Return quantity exceeds maximum of {$rules['max_return_quantity']}";
            }

            if (isset($item['original_quantity'])) {
                $returnPercentage = ($item['quantity'] / $item['original_quantity']) * 100;
                if ($returnPercentage > $rules['max_return_percentage']) {
                    $validationErrors[] = "Return percentage exceeds maximum of {$rules['max_return_percentage']}%";
                }
            }
        }

        // Validate reason requirement
        if ($rules['require_reason'] && empty($returnData['reason'])) {
            $validationErrors[] = "Return reason is required";
        }

        return [
            'valid' => empty($validationErrors),
            'errors' => $validationErrors,
        ];
    }

    /**
     * Get return statistics for a business.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getReturnStatistics(int $businessId, array $filters = []): array
    {
        $saleReturnsQuery = SaleReturn::where('business_id', $businessId);
        $purchaseReturnsQuery = PurchaseReturn::where('business_id', $businessId);

        // Apply date filters
        if (isset($filters['from_date'])) {
            $saleReturnsQuery->where('return_date', '>=', $filters['from_date']);
            $purchaseReturnsQuery->where('return_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $saleReturnsQuery->where('return_date', '<=', $filters['to_date']);
            $purchaseReturnsQuery->where('return_date', '<=', $filters['to_date']);
        }

        $saleReturns = $saleReturnsQuery->get();
        $purchaseReturns = $purchaseReturnsQuery->get();

        return [
            'sale_returns' => [
                'total_count' => $saleReturns->count(),
                'total_refund_amount' => $saleReturns->sum('refund_amount'),
                'by_status' => $saleReturns->groupBy('status')->map->count(),
                'by_reason' => $saleReturns->groupBy('reason')->map->count(),
            ],
            'purchase_returns' => [
                'total_count' => $purchaseReturns->count(),
                'total_credit_amount' => $purchaseReturns->sum('credit_amount'),
                'by_status' => $purchaseReturns->groupBy('status')->map->count(),
                'by_reason' => $purchaseReturns->groupBy('reason')->map->count(),
            ],
            'net_financial_impact' => $purchaseReturns->sum('credit_amount') - $saleReturns->sum('refund_amount'),
        ];
    }
}