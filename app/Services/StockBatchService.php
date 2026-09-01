<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StockBatchService
{
    use WithTransactionalOperations;

    /**
     * Create a new stock batch.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return Stock
     * @throws \Exception
     */
    public function createBatch(array $data, int $businessId): Stock
    {
        return $this->executeTransaction(function () use ($data, $businessId) {
            $stock = Stock::create([
                'business_id' => $businessId,
                'product_id' => $data['product_id'],
                'batch_no' => $data['batch_no'] ?? $this->generateBatchNumber($businessId, $data['product_id']),
                'expire_date' => $data['expire_date'] ?? null,
                'productStock' => $data['quantity'] ?? 0,
            ]);

            // Log stock movement
            $this->logStockMovement($stock, 'in', $data['quantity'] ?? 0, 'batch_creation', $stock->id, $businessId);

            return $stock->fresh();
        });
    }

    /**
     * Merge multiple batches into one.
     *
     * @param array<int> $batchIds
     * @param int $businessId
     * @return Stock
     * @throws \Exception
     */
    public function mergeBatches(array $batchIds, int $businessId): Stock
    {
        return $this->executeTransaction(function () use ($batchIds, $businessId) {
            $batches = Stock::whereIn('id', $batchIds)
                ->where('business_id', $businessId)
                ->get();

            if ($batches->count() < 2) {
                throw new \Exception('At least 2 batches are required for merging');
            }

            // Validate same product
            $productId = $batches->first()->product_id;
            if ($batches->pluck('product_id')->unique()->count() > 1) {
                throw new \Exception('All batches must belong to the same product');
            }

            // Calculate totals
            $totalQuantity = $batches->sum('productStock');
            $earliestExpiry = $batches->pluck('expire_date')->filter()->min();

            // Create merged batch
            $mergedBatch = Stock::create([
                'business_id' => $businessId,
                'product_id' => $productId,
                'batch_no' => $this->generateBatchNumber($businessId, $productId, 'MERGED'),
                'expire_date' => $earliestExpiry,
                'productStock' => $totalQuantity,
            ]);

            // Log movement for merged batch
            $this->logStockMovement($mergedBatch, 'in', $totalQuantity, 'batch_merge', $mergedBatch->id, $businessId);

            // Delete old batches and log movements
            foreach ($batches as $batch) {
                $this->logStockMovement($batch, 'adjustment', -$batch->productStock, 'batch_merge_delete', $mergedBatch->id, $businessId);
                $batch->delete();
            }

            return $mergedBatch->fresh();
        });
    }

    /**
     * Split a batch into two.
     *
     * @param int $batchId
     * @param int $quantity
     * @param int $businessId
     * @return array
     * @throws \Exception
     */
    public function splitBatch(int $batchId, int $quantity, int $businessId): array
    {
        return $this->executeTransaction(function () use ($batchId, $quantity, $businessId) {
            $originalBatch = Stock::findOrFail($batchId);

            if ($originalBatch->business_id !== $businessId) {
                throw new \Exception('Batch does not belong to this business');
            }

            if ($quantity >= $originalBatch->productStock) {
                throw new \Exception('Split quantity must be less than batch quantity');
            }

            $remainingQuantity = $originalBatch->productStock - $quantity;

            // Update original batch
            $originalBatch->update(['productStock' => $remainingQuantity]);
            $this->logStockMovement($originalBatch, 'adjustment', -$quantity, 'batch_split', $originalBatch->id, $businessId);

            // Create new batch
            $newBatch = Stock::create([
                'business_id' => $businessId,
                'product_id' => $originalBatch->product_id,
                'batch_no' => $this->generateBatchNumber($businessId, $originalBatch->product_id, 'SPLIT'),
                'expire_date' => $originalBatch->expire_date,
                'productStock' => $quantity,
            ]);

            $this->logStockMovement($newBatch, 'in', $quantity, 'batch_split', $newBatch->id, $businessId);

            return [
                'original_batch' => $originalBatch->fresh(),
                'new_batch' => $newBatch->fresh(),
            ];
        });
    }

    /**
     * Get batches expiring within specified days.
     *
     * @param int $businessId
     * @param int $days
     * @return Collection
     */
    public function getExpiringBatches(int $businessId, int $days = 30): Collection
    {
        $expiryDate = Carbon::now()->addDays($days);

        return Stock::where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->whereNotNull('expire_date')
            ->where('expire_date', '<=', $expiryDate)
            ->where('expire_date', '>=', now())
            ->with('product:id,productName')
            ->orderBy('expire_date', 'asc')
            ->get();
    }

    /**
     * Get expired batches.
     *
     * @param int $businessId
     * @return Collection
     */
    public function getExpiredBatches(int $businessId): Collection
    {
        return Stock::where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->whereNotNull('expire_date')
            ->where('expire_date', '<', now())
            ->with('product:id,productName')
            ->orderBy('expire_date', 'asc')
            ->get();
    }

    /**
     * Get batch summary by product.
     *
     * @param int $businessId
     * @param int|null $productId
     * @return Collection
     */
    public function getBatchSummary(int $businessId, ?int $productId = null): Collection
    {
        $query = Stock::where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->with('product:id,productName,purchase_without_tax,sales_price');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->orderBy('expire_date', 'asc')
            ->get()
            ->groupBy('product_id');
    }

    /**
     * Adjust batch quantity.
     *
     * @param int $batchId
     * @param int $adjustment
     * @param string $reason
     * @param int $businessId
     * @return Stock
     * @throws \Exception
     */
    public function adjustBatchQuantity(int $batchId, int $adjustment, string $reason, int $businessId): Stock
    {
        return $this->executeTransaction(function () use ($batchId, $adjustment, $reason, $businessId) {
            $batch = Stock::findOrFail($batchId);

            if ($batch->business_id !== $businessId) {
                throw new \Exception('Batch does not belong to this business');
            }

            $newQuantity = $batch->productStock + $adjustment;

            if ($newQuantity < 0) {
                throw new \Exception('Cannot adjust quantity below zero');
            }

            $batch->update(['productStock' => $newQuantity]);

            $movementType = $adjustment > 0 ? 'in' : 'out';
            $this->logStockMovement($batch, $movementType, $adjustment, $reason, $batch->id, $businessId);

            return $batch->fresh();
        });
    }

    /**
     * Transfer stock between batches of same product.
     *
     * @param int $fromBatchId
     * @param int $toBatchId
     * @param int $quantity
     * @param int $businessId
     * @return array
     * @throws \Exception
     */
    public function transferBetweenBatches(int $fromBatchId, int $toBatchId, int $quantity, int $businessId): array
    {
        return $this->executeTransaction(function () use ($fromBatchId, $toBatchId, $quantity, $businessId) {
            $fromBatch = Stock::findOrFail($fromBatchId);
            $toBatch = Stock::findOrFail($toBatchId);

            if ($fromBatch->business_id !== $businessId || $toBatch->business_id !== $businessId) {
                throw new \Exception('Both batches must belong to this business');
            }

            if ($fromBatch->product_id !== $toBatch->product_id) {
                throw new \Exception('Both batches must belong to the same product');
            }

            if ($fromBatch->productStock < $quantity) {
                throw new \Exception('Insufficient quantity in source batch');
            }

            // Transfer stock
            $fromBatch->decrement('productStock', $quantity);
            $toBatch->increment('productStock', $quantity);

            // Log movements
            $this->logStockMovement($fromBatch, 'out', $quantity, 'batch_transfer', $toBatch->id, $businessId);
            $this->logStockMovement($toBatch, 'in', $quantity, 'batch_transfer', $fromBatch->id, $businessId);

            return [
                'from_batch' => $fromBatch->fresh(),
                'to_batch' => $toBatch->fresh(),
            ];
        });
    }

    /**
     * Get batch movement history.
     *
     * @param int $batchId
     * @param int $businessId
     * @return Collection
     */
    public function getBatchMovementHistory(int $batchId, int $businessId): Collection
    {
        return StockMovement::where('stock_id', $batchId)
            ->where('business_id', $businessId)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Generate a unique batch number.
     *
     * @param int $businessId
     * @param int $productId
     * @param string|null $prefix
     * @return string
     */
    protected function generateBatchNumber(int $businessId, int $productId, ?string $prefix = null): string
    {
        $product = Product::findOrFail($productId);
        $basePrefix = $prefix ?? 'BATCH';
        $date = now()->format('Ymd');
        $sequence = Stock::where('business_id', $businessId)
            ->where('product_id', $productId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%s-%04d', $basePrefix, $product->productCode ?? 'PROD', $date, $sequence);
    }

    /**
     * Log stock movement.
     *
     * @param Stock $stock
     * @param string $movementType
     * @param int $quantity
     * @param string $referenceType
     * @param int $referenceId
     * @param int $businessId
     * @return void
     */
    protected function logStockMovement(Stock $stock, string $movementType, int $quantity, string $referenceType, int $referenceId, int $businessId): void
    {
        StockMovement::create([
            'business_id' => $businessId,
            'product_id' => $stock->product_id,
            'stock_id' => $stock->id,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'before_quantity' => $stock->productStock - $quantity,
            'after_quantity' => $stock->productStock,
            'batch_no' => $stock->batch_no,
            'expire_date' => $stock->expire_date,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => "Stock movement via {$referenceType}",
        ]);
    }
}