<?php

namespace App\Services\Stock;

use App\Models\Stock;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;

/**
 * Stock Allocation Service
 *
 * BUG-S9 FIX: Thread-safe stock updates using pessimistic locking
 *
 * مسؤول عن تخصيص وتحديث المخزون بشكل آمن من التزامن
 * يستخدم pessimistic locking (lockForUpdate) لمنع race conditions
 * يدعم كلا نموذجي المخزون القديم (Stock) والجديد (ProductStock)
 */
class StockAllocationService
{
    /**
     * Allocate (decrease) stock quantity safely with pessimistic locking - Legacy Stock model
     *
     * @param int $productId Product ID
     * @param int $quantity Quantity to allocate
     * @param int|null $batchId Optional batch ID for batch tracking
     * @return bool True if allocation successful
     * @throws \Exception If stock is insufficient
     */
    public static function allocate(int $productId, int $quantity, ?int $batchId = null): bool
    {
        return static::allocateToStock($productId, $quantity, null, $batchId);
    }

    /**
     * Allocate (decrease) stock quantity safely with pessimistic locking - ProductStock model
     *
     * @param int $productId Product ID
     * @param int $quantity Quantity to allocate
     * @param int|null $branchId Optional branch ID
     * @param int|null $batchId Optional batch ID (ProductStock ID)
     * @return bool True if allocation successful
     * @throws \Exception If stock is insufficient
     */
    public static function allocateToProductStock(int $productId, int $quantity, ?int $branchId = null, ?int $batchId = null): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $branchId, $batchId) {
            $query = ProductStock::query()
                ->where('product_id', $productId);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            if ($batchId) {
                $query->where('id', $batchId);
            }

            $stock = $query->lockForUpdate()->first();

            if (!$stock) {
                throw new \Exception("ProductStock record not found for product {$productId}");
            }

            if ($stock->quantity < $quantity) {
                throw new \Exception(
                    "Insufficient stock. Available: {$stock->quantity}, Requested: {$quantity}"
                );
            }

            $stock->decrement('quantity', $quantity);

            return true;
        });
    }

    /**
     * Internal method to allocate to legacy Stock model for backward compatibility
     */
    protected static function allocateToStock(int $productId, int $quantity, ?int $branchId = null, ?int $batchId = null): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $batchId) {
            $query = Stock::query()
                ->where('product_id', $productId);

            if ($batchId) {
                $query->where('id', $batchId);
            }

            $stock = $query->lockForUpdate()->first();

            if (!$stock) {
                throw new \Exception("Stock record not found for product {$productId}");
            }

            if ($stock->productStock < $quantity) {
                throw new \Exception(
                    "Insufficient stock. Available: {$stock->productStock}, Requested: {$quantity}"
                );
            }

            $stock->decrement('productStock', $quantity);

            return true;
        });
    }

    /**
     * Release (increase) stock quantity safely with pessimistic locking - Legacy Stock model
     * Used for refunds, returns, or cancellations
     *
     * @param int $productId Product ID
     * @param int $quantity Quantity to release
     * @param int|null $batchId Optional batch ID
     * @return bool True if release successful
     */
    public static function release(int $productId, int $quantity, ?int $batchId = null): bool
    {
        return static::releaseFromStock($productId, $quantity, null, $batchId);
    }

    /**
     * Release (increase) stock quantity safely with pessimistic locking - ProductStock model
     * Used for refunds, returns, or cancellations
     *
     * @param int $productId Product ID
     * @param int $quantity Quantity to release
     * @param int|null $branchId Optional branch ID
     * @param int|null $batchId Optional batch ID (ProductStock ID)
     * @return bool True if release successful
     */
    public static function releaseFromProductStock(int $productId, int $quantity, ?int $branchId = null, ?int $batchId = null): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $branchId, $batchId) {
            $query = ProductStock::query()
                ->where('product_id', $productId);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            if ($batchId) {
                $query->where('id', $batchId);
            }

            $stock = $query->lockForUpdate()->first();

            if (!$stock) {
                throw new \Exception("ProductStock record not found for product {$productId}");
            }

            $stock->increment('quantity', $quantity);

            return true;
        });
    }

    /**
     * Internal method to release from legacy Stock model for backward compatibility
     */
    protected static function releaseFromStock(int $productId, int $quantity, ?int $branchId = null, ?int $batchId = null): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $batchId) {
            $query = Stock::query()
                ->where('product_id', $productId);

            if ($batchId) {
                $query->where('id', $batchId);
            }

            $stock = $query->lockForUpdate()->first();

            if (!$stock) {
                throw new \Exception("Stock record not found for product {$productId}");
            }

            $stock->increment('productStock', $quantity);

            return true;
        });
    }

    /**
     * Get stock quantity safely with pessimistic locking - Legacy Stock model
     *
     * @param int $productId Product ID
     * @param int|null $batchId Optional batch ID
     * @return int|null Current stock quantity
     */
    public static function getLockedQuantity(int $productId, ?int $branchId = null, ?int $batchId = null): ?int
    {
        return static::getLockedQuantityFromStock($productId, $batchId);
    }

    /**
     * Get stock quantity safely with pessimistic locking - ProductStock model
     *
     * @param int $productId Product ID
     * @param int|null $branchId Optional branch ID
     * @param int|null $batchId Optional batch ID (ProductStock ID)
     * @return int|null Current stock quantity
     */
    public static function getLockedQuantityFromProductStock(int $productId, ?int $branchId = null, ?int $batchId = null): ?int
    {
        $query = ProductStock::query()
            ->where('product_id', $productId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($batchId) {
            $query->where('id', $batchId);
        }

        $stock = $query->lockForUpdate()->first();

        return $stock?->quantity;
    }

    /**
     * Internal method to get quantity from legacy Stock model for backward compatibility
     */
    protected static function getLockedQuantityFromStock(int $productId, ?int $batchId = null): ?int
    {
        $query = Stock::query()
            ->where('product_id', $productId);

        if ($batchId) {
            $query->where('id', $batchId);
        }

        $stock = $query->lockForUpdate()->first();

        return $stock?->productStock;
    }

    /**
     * Check if stock is available with pessimistic locking
     *
     * @param int $productId Product ID
     * @param int $quantity Required quantity
     * @param int|null $branchId Optional branch ID (for ProductStock)
     * @param int|null $batchId Optional batch ID
     * @return bool True if sufficient stock available
     */
    public static function isAvailable(int $productId, int $quantity, ?int $branchId = null, ?int $batchId = null): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $branchId, $batchId) {
            $currentQuantity = $branchId
                ? static::getLockedQuantityFromProductStock($productId, $branchId, $batchId)
                : static::getLockedQuantityFromStock($productId, $batchId);
            return ($currentQuantity ?? 0) >= $quantity;
        });
    }

    /**
     * Add (increase/create) stock quantity safely with pessimistic locking - ProductStock model
     * Used when receiving stock from purchase orders, GRN, or stock transfers
     *
     * @param array $data ProductStock data (product_id, branch_id, company_id, quantity, batch_number, etc.)
     * @return bool True if operation successful
     */
    public static function addToProductStock(array $data): bool
    {
        return DB::transaction(function () use ($data) {
            $query = ProductStock::query()
                ->where('product_id', $data['product_id'])
                ->where('branch_id', $data['branch_id']);

            if (!empty($data['batch_number'])) {
                $query->where('batch_number', $data['batch_number']);
            }

            $stock = $query->lockForUpdate()->first();

            if ($stock) {
                $stock->increment('quantity', $data['quantity']);
                if (!empty($data['expiry_date'])) {
                    $stock->expiry_date = $data['expiry_date'];
                }
                if (!empty($data['batch_number'])) {
                    $stock->batch_number = $data['batch_number'];
                }
                $stock->save();
            } else {
                ProductStock::create([
                    'company_id' => $data['company_id'],
                    'product_id' => $data['product_id'],
                    'branch_id' => $data['branch_id'],
                    'quantity' => $data['quantity'],
                    'batch_number' => $data['batch_number'] ?? null,
                    'expiry_date' => $data['expiry_date'] ?? null,
                    'reorder_level' => $data['reorder_level'] ?? 10,
                    'reorder_quantity' => $data['reorder_quantity'] ?? 50,
                    'is_active' => true,
                ]);
            }

            return true;
        });
    }

    /**
     * Add (increase/create) stock quantity safely with pessimistic locking - Legacy Stock model
     *
     * @param int $productId Product ID
     * @param int $quantity Quantity to add
     * @param string|null $batchNo Batch number
     * @param string|null $expireDate Expiry date
     * @param int|null $businessId Business ID
     * @return bool True if operation successful
     */
    public static function addToLegacyStock(int $productId, int $quantity, ?string $batchNo = null, ?string $expireDate = null, ?int $businessId = null): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $batchNo, $expireDate, $businessId) {
            $query = Stock::query()->where('product_id', $productId);

            if ($batchNo) {
                $query->where('batch_no', $batchNo);
            }

            $stock = $query->lockForUpdate()->first();

            if ($stock) {
                $stock->increment('productStock', $quantity);
                if ($batchNo) {
                    $stock->batch_no = $batchNo;
                }
                if ($expireDate) {
                    $stock->expire_date = $expireDate;
                }
                $stock->save();
            } else {
                Stock::create([
                    'product_id' => $productId,
                    'batch_no' => $batchNo,
                    'expire_date' => $expireDate,
                    'productStock' => $quantity,
                    'company_id' => $businessId,
                ]);
            }

            return true;
        });
    }
}
</parameter>
