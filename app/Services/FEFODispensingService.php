<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class FEFODispensingService
{
    /**
     * Dispense stock using FEFO (First Expired, First Out) logic.
     *
     * @param int $productId
     * @param int $quantity
     * @param int $businessId
     * @return array Array of stock batches with quantities to dispense
     * @throws InsufficientStockException
     */
    public function dispense(int $productId, int $quantity, int $businessId): array
    {
        // Get available stock batches ordered by expiry date (FEFO)
        $stockBatches = Stock::where('product_id', $productId)
            ->where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->orderBy('expire_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        if ($stockBatches->isEmpty()) {
            throw new InsufficientStockException('Insufficient stock available', [
                'product_id' => $productId,
                'requested' => $quantity,
                'available' => 0,
            ]);
        }

        $totalAvailable = $stockBatches->sum('productStock');

        if ($totalAvailable < $quantity) {
            throw new InsufficientStockException('Insufficient stock available', [
                'product_id' => $productId,
                'requested' => $quantity,
                'available' => $totalAvailable,
            ]);
        }

        $dispensingPlan = [];
        $remainingQuantity = $quantity;

        foreach ($stockBatches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $quantityFromBatch = min($batch->productStock, $remainingQuantity);

            $dispensingPlan[] = [
                'stock_id' => $batch->id,
                'batch_no' => $batch->batch_no,
                'expire_date' => $batch->expire_date,
                'quantity' => $quantityFromBatch,
                'unit_cost' => $batch->cost_price ?? $batch->purchase_price,
            ];

            $remainingQuantity -= $quantityFromBatch;
        }

        return $dispensingPlan;
    }

    /**
     * Dispense stock from a specific batch (for manual selection).
     *
     * @param int $stockId
     * @param int $quantity
     * @param int $businessId
     * @return array
     * @throws InsufficientStockException
     */
    public function dispenseFromBatch(int $stockId, int $quantity, int $businessId): array
    {
        $stock = Stock::where('id', $stockId)
            ->where('business_id', $businessId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($stock->productStock < $quantity) {
            throw new InsufficientStockException($stock->product_id, $quantity, $stock->productStock);
        }

        return [
            [
                'stock_id' => $stock->id,
                'batch_no' => $stock->batch_no,
                'expire_date' => $stock->expire_date,
                'quantity' => $quantity,
                'unit_cost' => $stock->cost_price ?? $stock->purchase_price,
            ]
        ];
    }

    /**
     * Get product batches expiring soon (for expiry alerts).
     *
     * @param int $businessId
     * @param int $daysThreshold
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringBatches(int $businessId, int $daysThreshold = 30)
    {
        $thresholdDate = now()->addDays($daysThreshold);

        return Stock::where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->where('expire_date', '<=', $thresholdDate)
            ->where('expire_date', '>=', now())
            ->with('product')
            ->orderBy('expire_date', 'asc')
            ->get();
    }

    /**
     * Get expired batches (should be quarantined).
     *
     * @param int $businessId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiredBatches(int $businessId)
    {
        return Stock::where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->where('expire_date', '<', now())
            ->with('product')
            ->orderBy('expire_date', 'asc')
            ->get();
    }

    /**
     * Get product movement history for traceability.
     *
     * @param int $stockId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBatchMovementHistory(int $stockId)
    {
        return Stock::with([
            'product',
            'barcodes',
            'stockMovements' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])
        ->findOrFail($stockId);
    }

    /**
     * Get all sales that used a specific batch (for recall).
     *
     * @param int $stockId
     * @param int $businessId
     * @return \Illuminate\Database\Eloquent\Collection
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
                'parties.address as customer_address'
            )
            ->orderBy('sales.saleDate', 'desc')
            ->get();
    }

    /**
     * Check if batch is near expiry or expired.
     *
     * @param int $stockId
     * @return array
     */
    public function getBatchExpiryStatus(int $stockId): array
    {
        $stock = Stock::with('product')->findOrFail($stockId);

        if (!$stock->expire_date) {
            return [
                'status' => 'no_expiry_date',
                'days_until_expiry' => null,
                'is_expired' => false,
                'is_near_expiry' => false,
            ];
        }

        $daysUntilExpiry = now()->diffInDays($stock->expire_date, false);

        return [
            'status' => $daysUntilExpiry < 0 ? 'expired' : ($daysUntilExpiry <= 30 ? 'near_expiry' : 'ok'),
            'days_until_expiry' => $daysUntilExpiry,
            'is_expired' => $daysUntilExpiry < 0,
            'is_near_expiry' => $daysUntilExpiry >= 0 && $daysUntilExpiry <= 30,
            'expire_date' => $stock->expire_date->format('Y-m-d'),
        ];
    }
}
