<?php

namespace App\Services;

use App\Events\StockTransferApproved;
use App\Events\StockTransferCancelled;
use App\Events\StockTransferReceived;
use App\Events\StockTransferRejected;
use App\Events\StockTransferShipped;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Inventory;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\Stock\StockAllocationService;
use App\Services\StockBatchService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * StockTransferService
 *
 * Service layer for managing stock transfer operations.
 * Handles business logic for the complete transfer workflow:
 * - Request → Approve → Ship → Receive
 *
 * Integrates with StockMovementService for centralized stock logging
 * and uses proper cache tagging for efficient invalidation.
 *
 * @version 2.0.0
 */
class StockTransferService extends BaseService
{
    /**
     * The StockMovementService instance.
     */
    protected StockMovementService $stockMovementService;

    /**
     * The FefoStockService instance.
     */
    protected FefoStockService $fefoStockService;

    /**
     * The StockBatchService instance.
     */
    protected StockBatchService $stockBatchService;

    /**
     * StockTransferService constructor.
     */
    public function __construct(
        StockMovementService $stockMovementService,
        FefoStockService $fefoStockService,
        StockBatchService $stockBatchService
    ) {
        $this->stockMovementService = $stockMovementService;
        $this->fefoStockService = $fefoStockService;
        $this->stockBatchService = $stockBatchService;
    }

    /**
     * Get a unique cache key prefix for a company.
     */
    protected function getCachePrefix(int $companyId): string
    {
        return "stock_transfer:{$companyId}";
    }

    /**
     * Invalidate all transfer-related cache for a company.
     */
    protected function clearTransferCache(int $companyId): void
    {
        $prefix = $this->getCachePrefix($companyId);

        // Use tagged cache keys with a version-based approach for reliable invalidation
        $cacheVersionKey = "{$prefix}:version";
        $version = Cache::get($cacheVersionKey, 1);
        Cache::forever($cacheVersionKey, $version + 1);

        // Also clear specific known cache keys
        Cache::forget("{$prefix}:stats:*");
        Cache::forget("company:{$companyId}:transfer_stats:*");
        Cache::forget("company:{$companyId}:transfers:*");
    }

    /**
     * Get cache key with version.
     */
    protected function getCacheKey(int $companyId, string $suffix): string
    {
        $version = Cache::get($this->getCachePrefix($companyId) . ':version', 1);
        return "{$this->getCachePrefix($companyId)}:{$suffix}:v{$version}";
    }

    /**
     * Create a new stock transfer request.
     *
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

                StockTransferItem::create([
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

            // Log stock movement (reservation)
            foreach ($data['items'] as $itemData) {
                $this->stockMovementService->logMovement(
                    $itemData['product_id'],
                    $data['from_branch_id'],
                    'out',
                    $itemData['quantity_requested'],
                    'stock_transfer_request',
                    $transfer->id,
                    $itemData['batch_number'] ?? null,
                    ['transfer_number' => $transfer->transfer_number, 'status' => 'pending']
                );
            }

            return $transfer->load(['items.product', 'fromBranch', 'toBranch', 'requestedBy']);
        });
    }

    /**
     * Approve a stock transfer.
     *
     * @throws \Exception
     */
    public function approveTransfer(StockTransfer $transfer, int $approverId): StockTransfer
    {
        if (! $transfer->canBeApproved()) {
            throw new \Exception("Cannot approve transfer with status: {$transfer->status}");
        }

        return DB::transaction(function () use ($transfer, $approverId) {
            $transfer->update([
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            // Dispatch approval event
            event(new StockTransferApproved($transfer));

            // Clear cache
            $this->clearTransferCache($transfer->company_id);

            return $transfer->fresh();
        });
    }

    /**
     * Reject a stock transfer.
     *
     * @throws \Exception
     */
    public function rejectTransfer(StockTransfer $transfer, int $rejecterId, string $reason): StockTransfer
    {
        if (! $transfer->canBeRejected()) {
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
            event(new StockTransferRejected($transfer));

            // Clear cache
            $this->clearTransferCache($transfer->company_id);

            return $transfer->fresh();
        });
    }

    /**
     * Ship a stock transfer (deduct stock from source branch).
     * Uses FEFO to allocate from nearest-expiry batches first.
     *
     * @throws \Exception
     */
    public function shipTransfer(StockTransfer $transfer, int $shipperId, array $itemsData): StockTransfer
    {
        if (! $transfer->canBeShipped()) {
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

                // Allocate via FEFO from source branch
                $allocations = $this->fefoStockService->allocate(
                    $transferItem->product_id,
                    $transfer->from_branch_id,
                    (float) $itemData['quantity_sent']
                );

                // Deduct stock via FefoStockService (deducts Inventory + StockBatch + logs movement)
                $this->fefoStockService->deduct(
                    $allocations,
                    'stock_transfer_ship',
                    $transfer->id
                );

                // Update batch info on transfer item from actual allocation
                if (!empty($allocations[0]['batch_number'])) {
                    $transferItem->update([
                        'batch_number' => $allocations[0]['batch_number'],
                    ]);
                }
            }

            // Update transfer status
            $transfer->update([
                'status' => 'in_transit',
                'shipped_by' => $shipperId,
                'shipped_at' => now(),
            ]);

            // Dispatch shipping event
            event(new StockTransferShipped($transfer));

            // Clear cache
            $this->clearTransferCache($transfer->company_id);

            return $transfer->fresh()->load(['items']);
        });
    }

    /**
     * Receive a stock transfer (add stock to destination branch).
     * Uses FefoStockService to restore stock with full batch tracking.
     *
     * @throws \Exception
     */
    public function receiveTransfer(StockTransfer $transfer, int $receiverId, array $itemsData, ?string $notes = null): StockTransfer
    {
        if (! $transfer->canBeReceived()) {
            throw new \Exception("Cannot receive transfer with status: {$transfer->status}");
        }

        return DB::transaction(function () use ($transfer, $receiverId, $itemsData, $notes) {
            // Update transfer items with receiving details
            foreach ($itemsData as $itemData) {
                $transferItem = $transfer->items()->findOrFail($itemData['id']);

                $transferItem->update([
                    'quantity_received' => $itemData['quantity_received'],
                ]);

                // Restore stock to destination branch via FefoStockService
                $this->fefoStockService->restore(
                    [
                        [
                            'product_id' => $transferItem->product_id,
                            'branch_id' => $transfer->to_branch_id,
                            'batch_number' => $transferItem->batch_number,
                            'expiry_date' => $transferItem->expiry_date?->toDateString(),
                            'quantity' => $itemData['quantity_received'],
                            'unit_price' => $transferItem->unit_cost,
                            'cost_price' => $transferItem->unit_cost,
                            'company_id' => $transfer->company_id,
                        ]
                    ],
                    'stock_transfer_receive',
                    $transfer->id
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
            event(new StockTransferReceived($transfer));

            // Clear cache
            $this->clearTransferCache($transfer->company_id);

            return $transfer->fresh()->load(['items']);
        });
    }

    /**
     * Cancel a stock transfer.
     *
     * @throws \Exception
     */
    public function cancelTransfer(StockTransfer $transfer, int $cancellerId, ?string $reason = null): StockTransfer
    {
        if (! $transfer->canBeCancelled()) {
            throw new \Exception("Cannot cancel transfer with status: {$transfer->status}");
        }

        return DB::transaction(function () use ($transfer, $reason) {
            // If transfer was already shipped, restore stock to source branch
            if ($transfer->status === 'in_transit') {
                foreach ($transfer->items as $item) {
                    if ($item->quantity_sent > 0) {
                        // Restore stock using StockAllocationService
                        StockAllocationService::addToProductStock([
                            'company_id' => $transfer->company_id,
                            'product_id' => $item->product_id,
                            'branch_id' => $transfer->from_branch_id,
                            'quantity' => $item->quantity_sent,
                            'batch_number' => $item->batch_number,
                            'expiry_date' => $item->expiry_date?->toDateString(),
                        ]);
                    }
                }
            }

            $transfer->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => $reason ? $transfer->notes."\n\nCancellation: ".$reason : $transfer->notes,
            ]);

            // Dispatch cancellation event
            event(new StockTransferCancelled($transfer));

            // Clear cache
            $this->clearTransferCache($transfer->company_id);

            return $transfer->fresh();
        });
    }

    /**
     * Deduct stock from a branch.
     *
     * @throws \Exception
     * @deprecated Use StockAllocationService::allocateToProductStock() instead
     */
    protected function deductStock(int $productId, int $branchId, float $quantity, ?string $batchNumber = null, ?int $transferId = null): void
    {
        StockAllocationService::allocateToProductStock(
            $productId,
            (int) $quantity,
            $branchId
        );

        // Use centralized StockMovementService
        $this->stockMovementService->logMovement(
            $productId,
            $branchId,
            'out',
            $quantity,
            'stock_transfer_ship',
            $transferId,
            $batchNumber
        );
    }

    /**
     * Add stock to a branch.
     *
     * @deprecated Use StockAllocationService::addToProductStock() instead
     */
    protected function addStock(int $productId, int $branchId, float $quantity, ?string $batchNumber = null, ?Carbon $expiryDate = null, float $unitCost = 0, ?int $transferId = null): void
    {
        $product = Product::findOrFail($productId);

        StockAllocationService::addToProductStock([
            'company_id' => $product->company_id,
            'product_id' => $productId,
            'branch_id' => $branchId,
            'quantity' => $quantity,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate?->toDateString(),
            'reorder_level' => 10,
            'reorder_quantity' => 50,
        ]);

        // Use centralized StockMovementService
        $this->stockMovementService->logMovement(
            $productId,
            $branchId,
            'in',
            $quantity,
            'stock_transfer_receive',
            $transferId,
            $batchNumber
        );
    }

    /**
     * Get transfers for a company with optional filters.
     *
     * @return LengthAwarePaginator
     */
    public function getTransfers(int $companyId, array $filters = [])
    {
        $cacheKey = $this->getCacheKey($companyId, 'transfers:' . md5(json_encode($filters)));

        // Use a short cache TTL for listing operations
        return Cache::remember($cacheKey, 60, function () use ($companyId, $filters) {
            $query = StockTransfer::where('company_id', $companyId)
                ->with(['fromBranch:id,name', 'toBranch:id,name', 'requestedBy:id,name', 'items.product:id,name,sku']);

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
        });
    }

    /**
     * Get transfer statistics for a company.
     */
    public function getTransferStatistics(int $companyId, ?int $branchId = null, string $period = 'month'): array
    {
        $cacheKey = $this->getCacheKey($companyId, "stats:{$branchId}:{$period}");

        return Cache::remember($cacheKey, 300, function () use ($companyId, $branchId, $period) {
            $startDate = match ($period) {
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
}
