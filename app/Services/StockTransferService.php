<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\ProductStock;
use App\Models\Product;
use App\Exceptions\BranchLimitExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * StockTransferService
 * 
 * Service layer for managing stock transfer operations.
 * Handles business logic for the complete transfer workflow:
 * - Request → Approve → Ship → Receive
 * 
 * @author Z-Syst Development Team
 * @version 1.0.0
 */
class StockTransferService extends BaseService
{
    /**
     * Create a new stock transfer request.
     *
     * @param array $data
     * @param int $userId
     * @return StockTransfer
     * @throws \Exception
     */
    public function createTransfer(array $data, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($data, $userId) {
            $company = auth()->user()->company;
            
            // Create the stock transfer
            $transfer = StockTransfer::create([
                'company_id' => $company->id,
                'from_branch_id' => $data['from_branch_id'],
                'to_branch_id' => $data['to_branch_id'],
                'requested_by' => $userId,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'requested_at' => now(),
                'total_items' => 0,
                'total_quantity' => 0,
                'total_value' => 0,
            ]);

            // Create transfer items and calculate totals
            $totalItems = 0;
            $totalQuantity = 0;
            $totalValue = 0;

            foreach ($data['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $quantity = $itemData['quantity_requested'];
                $unitCost = $itemData['unit_cost'];
                $totalCost = $quantity * $unitCost;

                $transferItem = StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $itemData['product_id'],
                    'product_stock_id' => $itemData['product_stock_id'] ?? null,
                    'quantity_requested' => $quantity,
                    'quantity_sent' => 0,
                    'quantity_received' => 0,
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $totalItems++;
                $totalQuantity += $quantity;
                $totalValue += $totalCost;
            }

            // Update transfer totals
            $transfer->update([
                'total_items' => $totalItems,
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
            ]);

            // Clear relevant cache
            $this->clearTransferCache($company->id);

            return $transfer->load(['items.product', 'fromBranch', 'toBranch', 'requestedBy']);
        });
    }

    /**
     * Approve a stock transfer.
     *
     * @param StockTransfer $transfer
     * @param int $approverId
     * @return StockTransfer
     * @throws \Exception
     */
    public function approveTransfer(StockTransfer $transfer, int $approverId): StockTransfer
    {
        if (!$transfer->canBeApproved()) {
            throw new \Exception("Cannot approve transfer with status: {$transfer->status}");
        }

        return DB::transaction(function () use ($transfer, $approverId) {
            $transfer->update([
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            // Dispatch approval event
            event(new \App\Events\StockTransferApproved($transfer));

            // Clear cache
            $this->clearTransferCache($transfer->company_id);

            return $transfer->fresh();
        });
    }

    /**
     * Reject a stock transfer.
     *
     * @param StockTransfer $transfer
     * @param int $rejecterId
     * @param string $reason
     * @return StockTransfer
     * @throws \Exception
     */
    public function rejectTransfer(StockTransfer $transfer, int $rejecterId, string $reason): StockTransfer
    {
        if (!$transfer->canBeRejected()) {
            throw new \Exception("Cannot reject transfer with status: {$transfer->status}");
        }

        return DB::transaction(function () use ($transfer, $rejecterId, $reason) {
            $transfer->update([
                'status' => 'rejected',
                'approved_by' => $rejecterId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            // Dispatch rejection event
            event(new \App\Events\StockTransferRejected($transfer));

            // Clear cache
            $this->clearTransferCache($transfer->company_id);

            return $transfer->fresh();
        });
    }

    /**
     * Ship a stock transfer (deduct stock from source branch).
     *
     * @param StockTransfer $transfer
     * @param int $shipperId
     * @param array $itemsData
     * @return StockTransfer
     * @throws \Exception
     */
    public function shipTransfer(StockTransfer $transfer, int $shipperId, array $itemsData): StockTransfer
    {
        if (!$transfer->canBeShipped()) {
            throw new \Exception("Cannot ship transfer with status: {$transfer->status}");
        }

        return DB::transaction(function () use ($transfer, $shipperId, $itemsData) {
            // Update transfer items with shipping details
            foreach ($itemsData as $itemData) {
                $transferItem = $transfer->items()->findOrFail($itemData['id']);
                
                $transferItem->update([
                    'quantity_sent' => $itemData['quantity_sent'],
                    'batch_number' => $itemData['batch_number'] ?? $transferItem->batch_number,
                    'expiry_date' => $itemData['expiry_date'] ?? $transferItem->expiry_date,
                ]);

                // Deduct stock from source branch
                $this->deductStock(
                    $transferItem->product_id,
                    $transfer->from_branch_id,
                    $itemData['quantity_sent'],
                    $transferItem->batch_number
                );
            }

            // Update transfer status
            $transfer->update([
                'status' => 'in_transit',
                'shipped_by' => $shipperId,
                'shipped_at' => now(),
            ]);

            // Dispatch shipping event
            event(new \App\Events\StockTransferShipped($transfer));

            // Clear cache
            $this->clearTransferCache($transfer->company_id);

            return $transfer->fresh()->load(['items']);
        });
    }

    /**
     * Receive a stock transfer (add stock to destination branch).
     *
     * @param StockTransfer $transfer
     * @param int $receiverId
     * @param array $itemsData
     * @param string|null $notes
     * @return StockTransfer
     * @throws \Exception
     */
    public function receiveTransfer(StockTransfer $transfer, int $receiverId, array $itemsData, ?string $notes = null): StockTransfer
    {
        if (!$transfer->canBeReceived()) {
            throw new \Exception("Cannot receive transfer with status: {$transfer->status}");
        }

        return DB::transaction(function () use ($transfer, $receiverId, $itemsData, $notes) {
            // Update transfer items with receiving details
            foreach ($itemsData as $itemData) {
                $transferItem = $transfer->items()->findOrFail($itemData['id']);
                
                $transferItem->update([
                    'quantity_received' => $itemData['quantity_received'],
                ]);

                // Add stock to destination branch
                $this->addStock(
                    $transferItem->product_id,
                    $transfer->to_branch_id,
                    $itemData['quantity_received'],
                    $transferItem->batch_number,
                    $transferItem->expiry_date,
                    $transferItem->unit_cost
                );
            }

            // Update transfer status
            $transfer->update([
                'status' => 'received',
                'received_by' => $receiverId,
                'received_at' => now(),
                'notes' => $notes ?? $transfer->notes,
            ]);

            // Dispatch receiving event
            event(new \App\Events\StockTransferReceived($transfer));

            // Clear cache
            $this->clearTransferCache($transfer->company_id);

            return $transfer->fresh()->load(['items']);
        });
    }

    /**
     * Cancel a stock transfer.
     *
     * @param StockTransfer $transfer
     * @param int $cancellerId
     * @param string|null $reason
     * @return StockTransfer
     * @throws \Exception
     */
    public function cancelTransfer(StockTransfer $transfer, int $cancellerId, ?string $reason = null): StockTransfer
    {
        if (!$transfer->canBeCancelled()) {
            throw new \Exception("Cannot cancel transfer with status: {$transfer->status}");
        }

        return DB::transaction(function () use ($transfer, $cancellerId, $reason) {
            // If transfer was already shipped, restore stock to source branch
            if ($transfer->status === 'in_transit') {
                foreach ($transfer->items as $item) {
                    if ($item->quantity_sent > 0) {
                        $this->addStock(
                            $item->product_id,
                            $transfer->from_branch_id,
                            $item->quantity_sent,
                            $item->batch_number,
                            $item->expiry_date,
                            $item->unit_cost
                        );
                    }
                }
            }

            $transfer->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => $reason ? $transfer->notes . "\n\nCancellation: " . $reason : $transfer->notes,
            ]);

            // Dispatch cancellation event
            event(new \App\Events\StockTransferCancelled($transfer));

            // Clear cache
            $this->clearTransferCache($transfer->company_id);

            return $transfer->fresh();
        });
    }

    /**
     * Deduct stock from a branch.
     *
     * @param int $productId
     * @param int $branchId
     * @param float $quantity
     * @param string|null $batchNumber
     * @return void
     * @throws \Exception
     */
    protected function deductStock(int $productId, int $branchId, float $quantity, ?string $batchNumber = null): void
    {
        $query = ProductStock::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('is_active', true);

        if ($batchNumber) {
            $query->where('batch_number', $batchNumber);
        }

        $stock = $query->first();

        if (!$stock) {
            // Try fallback to any stock record for the same branch if batch lookup failed
            $stock = ProductStock::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();
        }

        if (!$stock) {
            throw new \Exception("Stock record not found for product {$productId} at branch {$branchId}");
        }

        if ($stock->quantity < $quantity) {
            throw new \Exception("Insufficient stock. Available: {$stock->quantity}, Required: {$quantity}");
        }

        $stock->decrement('quantity', $quantity);

        $this->logStockMovement($productId, $branchId, 'out', $quantity, 'stock_transfer', $stock->batch_number);
    }

    /**
     * Add stock to a branch.
     *
     * @param int $productId
     * @param int $branchId
     * @param float $quantity
     * @param string|null $batchNumber
     * @param \Carbon\Carbon|null $expiryDate
     * @param float $unitCost
     * @return void
     */
    protected function addStock(int $productId, int $branchId, float $quantity, ?string $batchNumber = null, ?Carbon $expiryDate = null, float $unitCost = 0): void
    {
        // Find existing branch-level stock record for the product.
        $stock = ProductStock::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();

        if ($stock) {
            // Update existing stock quantities.
            $stock->increment('quantity', $quantity);

            if ($batchNumber) {
                $stock->batch_number = $batchNumber;
            }

            if ($expiryDate) {
                $stock->expiry_date = $expiryDate;
            }

            $stock->save();
        } else {
            $product = Product::findOrFail($productId);
            
            ProductStock::create([
                'company_id' => $product->company_id,
                'product_id' => $productId,
                'branch_id' => $branchId,
                'quantity' => $quantity,
                'reorder_level' => 10,
                'reorder_quantity' => 50,
                'batch_number' => $batchNumber,
                'expiry_date' => $expiryDate,
                'is_active' => true,
            ]);
        }

        // Log stock movement
        $this->logStockMovement($productId, $branchId, 'in', $quantity, 'stock_transfer', $batchNumber);
    }

    /**
     * Log stock movement (placeholder for future integration).
     *
     * @param int $productId
     * @param int $branchId
     * @param string $movementType
     * @param float $quantity
     * @param string $referenceType
     * @param string|null $batchNumber
     * @return void
     */
    protected function logStockMovement(int $productId, int $branchId, string $movementType, float $quantity, string $referenceType, ?string $batchNumber = null): void
    {
        // This would integrate with a StockMovementService when implemented
        // For now, we'll create a basic log entry
        \App\Models\ActivityLog::create([
            'company_id' => auth()->user()->company_id,
            'user_id' => auth()->id(),
            'action' => 'stock_movement',
            'description' => "Stock {$movementType}: {$quantity} units of product {$productId} at branch {$branchId} (Batch: {$batchNumber})",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'performed_at' => now(),
        ]);
    }

    /**
     * Get transfers for a company with optional filters.
     *
     * @param int $companyId
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTransfers(int $companyId, array $filters = [])
    {
        $query = StockTransfer::where('company_id', $companyId)
            ->with(['fromBranch', 'toBranch', 'requestedBy', 'items.product']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_branch_id'])) {
            $query->where('from_branch_id', $filters['from_branch_id']);
        }

        if (isset($filters['to_branch_id'])) {
            $query->where('to_branch_id', $filters['to_branch_id']);
        }

        if (isset($filters['branch_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('from_branch_id', $filters['branch_id'])
                  ->orWhere('to_branch_id', $filters['branch_id']);
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('requested_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('requested_at', '<=', $filters['date_to']);
        }

        // Search by transfer number
        if (isset($filters['search'])) {
            $query->where('transfer_number', 'like', "%{$filters['search']}%");
        }

        // Order by latest
        $query->orderBy('created_at', 'desc');

        // Paginate
        $perPage = $filters['per_page'] ?? 25;
        
        return $query->paginate($perPage);
    }

    /**
     * Get transfer statistics for a company.
     *
     * @param int $companyId
     * @param int|null $branchId
     * @param string $period
     * @return array
     */
    public function getTransferStatistics(int $companyId, ?int $branchId = null, string $period = 'month'): array
    {
        $cacheKey = "company:{$companyId}:transfer_stats:{$branchId}:{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($companyId, $branchId, $period) {
            $startDate = match($period) {
                'today' => Carbon::now()->startOfDay(),
                'week' => Carbon::now()->startOfWeek(),
                'month' => Carbon::now()->startOfMonth(),
                'year' => Carbon::now()->startOfYear(),
                default => Carbon::now()->startOfMonth(),
            };

            $query = StockTransfer::where('company_id', $companyId)
                ->where('created_at', '>=', $startDate);

            if ($branchId) {
                $query->where(function ($q) use ($branchId) {
                    $q->where('from_branch_id', $branchId)
                      ->orWhere('to_branch_id', $branchId);
                });
            }

            $transfers = $query->get();

            return [
                'total_transfers' => $transfers->count(),
                'pending_transfers' => $transfers->where('status', 'pending')->count(),
                'approved_transfers' => $transfers->where('status', 'approved')->count(),
                'in_transit_transfers' => $transfers->where('status', 'in_transit')->count(),
                'received_transfers' => $transfers->where('status', 'received')->count(),
                'rejected_transfers' => $transfers->where('status', 'rejected')->count(),
                'cancelled_transfers' => $transfers->where('status', 'cancelled')->count(),
                'total_value_transferred' => $transfers->where('status', 'received')->sum('total_value'),
                'total_quantity_transferred' => $transfers->where('status', 'received')->sum('total_quantity'),
            ];
        });
    }

    /**
     * Clear transfer-related cache.
     *
     * @param int $companyId
     * @return void
     */
    protected function clearTransferCache(int $companyId): void
    {
        $patterns = [
            "company:{$companyId}:transfer_stats:*",
            "company:{$companyId}:transfers:*",
        ];

        foreach ($patterns as $pattern) {
            // Note: Cache::forget doesn't support wildcards in all drivers
            // This is a simplified implementation
            Cache::forget($pattern);
        }
    }
}
