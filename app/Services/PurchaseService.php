<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\Stock;
use App\Traits\WithTransactionalOperations;

class PurchaseService
{
    use WithTransactionalOperations;

    /**
     * Create a new purchase with business logic validation.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @param int $userId
     * @return Purchase
     * @throws \Exception
     */
    public function createPurchase(array $data, int $businessId, int $userId): Purchase
    {
        return $this->executeTransaction(function () use ($data, $businessId, $userId) {
            // Update party due amount if applicable
            if (($data['dueAmount'] ?? 0) > 0 && ($data['party_id'] ?? null)) {
                $party = Party::findOrFail($data['party_id']);
                $party->update([
                    'due' => $party->due + $data['dueAmount'],
                ]);
            }

            // Update business balance
            $business = Business::findOrFail($businessId);
            $business->update([
                'remainingShopBalance' => $business->remainingShopBalance - ($data['paidAmount'] ?? 0),
            ]);

            // Create purchase
            $purchase = Purchase::create([
                'party_id' => $data['party_id'] ?? null,
                'business_id' => $businessId,
                'user_id' => $userId,
                'tax_id' => $data['tax_id'] ?? null,
                'discountAmount' => $data['discountAmount'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'dueAmount' => $data['dueAmount'] ?? 0,
                'paidAmount' => $data['paidAmount'] ?? 0,
                'totalAmount' => $data['totalAmount'] ?? 0,
                'invoiceNumber' => $this->generatePurchaseNumber($businessId),
                'isPaid' => $data['isPaid'] ?? false,
                'paymentType' => $data['paymentType'] ?? 'Cash',
                'purchaseDate' => $data['purchaseDate'] ?? now(),
                'note' => $data['note'] ?? null,
            ]);

            // Create purchase details and update stock
            $this->processPurchaseItems($purchase, $data['products'], $businessId);

            return $purchase->fresh(['details.product', 'party', 'tax']);
        });
    }

    /**
     * Update an existing purchase.
     *
     * @param Purchase $purchase
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return Purchase
     * @throws \Exception
     */
    public function updatePurchase(Purchase $purchase, array $data, int $businessId): Purchase
    {
        return $this->executeTransaction(function () use ($purchase, $data, $businessId) {
            // Restore previous stock
            $this->restorePurchaseStock($purchase);

            // Update purchase
            $purchase->update([
                'party_id' => $data['party_id'] ?? $purchase->party_id,
                'tax_id' => $data['tax_id'] ?? $purchase->tax_id,
                'discountAmount' => $data['discountAmount'] ?? $purchase->discountAmount,
                'dueAmount' => $data['dueAmount'] ?? $purchase->dueAmount,
                'isPaid' => $data['isPaid'] ?? $purchase->isPaid,
                'tax_amount' => $data['tax_amount'] ?? $purchase->tax_amount,
                'paidAmount' => $data['paidAmount'] ?? $purchase->paidAmount,
                'totalAmount' => $data['totalAmount'] ?? $purchase->totalAmount,
                'paymentType' => $data['paymentType'] ?? $purchase->paymentType,
                'purchaseDate' => $data['purchaseDate'] ?? $purchase->purchaseDate,
                'note' => $data['note'] ?? $purchase->note,
            ]);

            // Process new items
            if (isset($data['products']) && is_array($data['products'])) {
                $purchase->details()->delete();
                $this->processPurchaseItems($purchase, $data['products'], $businessId);
            }

            return $purchase->fresh(['details.product', 'party', 'tax']);
        });
    }

    /**
     * Delete a purchase and restore stock.
     *
     * @param Purchase $purchase
     * @return bool
     * @throws \Exception
     */
    public function deletePurchase(Purchase $purchase): bool
    {
        return $this->executeTransaction(function () use ($purchase) {
            $this->restorePurchaseStock($purchase);
            return $purchase->delete();
        });
    }

    /**
     * Link purchase to GRN.
     *
     * @param Purchase $purchase
     * @param int $grnId
     * @return Purchase
     */
    public function linkToGRN(Purchase $purchase, int $grnId): Purchase
    {
        $purchase->update([
            'grn_id' => $grnId,
        ]);

        return $purchase->fresh();
    }

    /**
     * Process purchase items with stock addition.
     *
     * @param Purchase $purchase
     * @param array $products
     * @param int $businessId
     * @return void
     */
    protected function processPurchaseItems(Purchase $purchase, array $products, int $businessId): void
    {
        foreach ($products as $productData) {
            $productId = $productData['product_id'];
            $quantity = $productData['quantities'];
            $batchNo = $productData['batch_no'] ?? null;
            $expireDate = $productData['expire_date'] ?? null;

            // Create purchase detail
            PurchaseDetails::create([
                'purchase_id' => $purchase->id,
                'product_id' => $productId,
                'batch_no' => $batchNo,
                'expire_date' => $expireDate,
                'quantities' => $quantity,
                'sales_price' => $productData['sales_price'] ?? 0,
                'profit_percent' => $productData['profit_percent'] ?? 0,
                'wholesale_price' => $productData['wholesale_price'] ?? 0,
                'purchase_with_tax' => $productData['purchase_with_tax'] ?? 0,
                'purchase_without_tax' => $productData['purchase_without_tax'] ?? 0,
            ]);

            // Update product pricing
            $product = Product::findOrFail($productId);
            $product->update([
                'sales_price' => $productData['sales_price'] ?? $product->sales_price,
                'profit_percent' => $productData['profit_percent'] ?? $product->profit_percent,
                'wholesale_price' => $productData['wholesale_price'] ?? $product->wholesale_price,
                'purchase_with_tax' => $productData['purchase_with_tax'] ?? $product->purchase_with_tax,
                'purchase_without_tax' => $productData['purchase_without_tax'] ?? $product->purchase_without_tax,
            ]);

            // Update or create stock
            if ($batchNo) {
                $stock = Stock::where('product_id', $productId)
                    ->where('business_id', $businessId)
                    ->where('batch_no', $batchNo)
                    ->first();
            } else {
                $stock = Stock::where('product_id', $productId)
                    ->where('business_id', $businessId)
                    ->first();
            }

            if ($stock) {
                $stock->update([
                    'batch_no' => $batchNo ?? $stock->batch_no,
                    'expire_date' => $expireDate ?? $stock->expire_date,
                    'productStock' => $stock->productStock + $quantity,
                ]);
            } else {
                Stock::create([
                    'business_id' => $businessId,
                    'product_id' => $productId,
                    'batch_no' => $batchNo,
                    'expire_date' => $expireDate,
                    'productStock' => $quantity,
                ]);
            }
        }
    }

    /**
     * Restore stock for a purchase (used in updates/deletes).
     *
     * @param Purchase $purchase
     * @return void
     */
    protected function restorePurchaseStock(Purchase $purchase): void
    {
        foreach ($purchase->details as $detail) {
            $stock = Stock::where('product_id', $detail->product_id)
                ->where('business_id', $purchase->business_id)
                ->where('batch_no', $detail->batch_no)
                ->first();

            if ($stock) {
                $newQuantity = $stock->productStock - $detail->quantities;
                if ($newQuantity >= 0) {
                    $stock->update(['productStock' => $newQuantity]);
                }
            }
        }
    }

    /**
     * Generate a unique purchase number.
     *
     * @param int $businessId
     * @return string
     */
    protected function generatePurchaseNumber(int $businessId): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $sequence = Purchase::where('business_id', $businessId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Calculate purchase totals.
     *
     * @param array $products
     * @return array
     */
    public function calculatePurchaseTotals(array $products): array
    {
        $subtotal = 0;
        $totalTax = 0;

        foreach ($products as $product) {
            $subtotal += ($product['purchase_without_tax'] ?? 0) * ($product['quantities'] ?? 0);
            $totalTax += (($product['purchase_with_tax'] ?? 0) - ($product['purchase_without_tax'] ?? 0)) * ($product['quantities'] ?? 0);
        }

        return [
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'total' => $subtotal + $totalTax,
        ];
    }
}