<?php

namespace App\Services;

use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class BatchRecallService
{
    /**
     * Initiate a product recall for a specific batch.
     *
     * @param int $stockId
     * @param string $reason
     * @param int $businessId
     * @return array Recall information
     */
    public function initiateRecall(int $stockId, string $reason, int $businessId): array
    {
        $stock = Stock::with('product')->where('id', $stockId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        // Get all sales that used this batch
        $affectedSales = $this->getBatchSales($stockId, $businessId);

        // Calculate recall statistics
        $totalQuantitySold = $affectedSales->sum('quantity');
        $totalCustomers = $affectedSales->count();
        $remainingStock = $stock->productStock;

        $recallInfo = [
            'stock_id' => $stock->id,
            'product_name' => $stock->product->productName,
            'batch_no' => $stock->batch_no,
            'expire_date' => $stock->expire_date?->format('Y-m-d'),
            'reason' => $reason,
            'total_quantity_sold' => $totalQuantitySold,
            'total_customers_affected' => $totalCustomers,
            'remaining_stock' => $remainingStock,
            'affected_sales' => $affectedSales,
            'recall_date' => now()->format('Y-m-d H:i:s'),
        ];

        return $recallInfo;
    }

    /**
     * Get all sales that used a specific batch.
     *
     * @param int $stockId
     * @param int $businessId
     * @return \Illuminate\Support\Collection
     */
    public function getBatchSales(int $stockId, int $businessId)
    {
        return DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('parties', 'sales.party_id', '=', 'parties.id')
            ->where('sale_details.stock_id', $stockId)
            ->where('sales.business_id', $businessId)
            ->select(
                'sales.id as sale_id',
                'sales.invoiceNumber',
                'sales.saleDate',
                'sale_details.quantity',
                'parties.name as customer_name',
                'parties.phone as customer_phone',
                'parties.address as customer_address',
                'parties.email as customer_email'
            )
            ->orderBy('sales.saleDate', 'desc')
            ->get();
    }

    /**
     * Quarantine a batch (set stock to 0 and flag as quarantined).
     *
     * @param int $stockId
     * @param int $businessId
     * @return bool
     */
    public function quarantineBatch(int $stockId, int $businessId): bool
    {
        $stock = Stock::where('id', $stockId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        $stock->update([
            'productStock' => 0,
            'notes' => ($stock->notes ?? '') . "\nQuarantined on " . now()->format('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Get recall report for multiple batches.
     *
     * @param array $stockIds
     * @param int $businessId
     * @return array
     */
    public function getMultiBatchRecallReport(array $stockIds, int $businessId): array
    {
        $report = [
            'total_batches' => count($stockIds),
            'total_customers_affected' => 0,
            'total_quantity_sold' => 0,
            'batches' => [],
        ];

        foreach ($stockIds as $stockId) {
            $stock = Stock::with('product')->where('id', $stockId)
                ->where('business_id', $businessId)
                ->first();

            if (!$stock) {
                continue;
            }

            $affectedSales = $this->getBatchSales($stockId, $businessId);

            $report['batches'][] = [
                'stock_id' => $stock->id,
                'product_name' => $stock->product->productName,
                'batch_no' => $stock->batch_no,
                'expire_date' => $stock->expire_date?->format('Y-m-d'),
                'quantity_sold' => $affectedSales->sum('quantity'),
                'customers_affected' => $affectedSales->count(),
            ];

            $report['total_customers_affected'] += $affectedSales->count();
            $report['total_quantity_sold'] += $affectedSales->sum('quantity');
        }

        return $report;
    }

    /**
     * Generate recall notification message for customers.
     *
     * @param array $recallInfo
     * @return string
     */
    public function generateRecallNotification(array $recallInfo): string
    {
        $message = "🚨 PRODUCT RECALL NOTICE 🚨\n\n";
        $message .= "Dear Customer,\n\n";
        $message .= "We regret to inform you that a product you purchased is subject to a recall.\n\n";
        $message .= "Product: {$recallInfo['product_name']}\n";
        $message .= "Batch Number: {$recallInfo['batch_no']}\n";
        $message .= "Expiry Date: {$recallInfo['expire_date']}\n\n";
        $message .= "Reason for Recall: {$recallInfo['reason']}\n\n";
        $message .= "Please discontinue use immediately and return the product to our pharmacy for a full refund.\n\n";
        $message .= "If you have any questions, please contact us immediately.\n\n";
        $message .= "Thank you for your understanding.\n";
        $message .= "Pharmacy Management Team";

        return $message;
    }
}
