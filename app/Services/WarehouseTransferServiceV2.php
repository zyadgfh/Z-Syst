<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferAudit;
use App\Models\TraceabilityLog;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced inter-warehouse stock transfer service.
 *
 * Features:
 * - Full validation before transfer (same-warehouse, sufficient stock, active warehouses)
 * - Complete audit trail for every action
 * - Transactional operations with rollback
 */
class WarehouseTransferServiceV2
{
    public const ACTION_CREATED = 'created';

    public const ACTION_COMPLETED = 'completed';

    public const ACTION_CANCELLED = 'cancelled';

    public const ACTION_FAILED = 'failed';

    /**
     * Create and validate a new stock transfer.
     *
     * @throws \Exception on validation failure
     */
    public function createTransfer(array $data, int $userId): StockTransfer
    {
        // Validate inputs
        $this->validateTransferData($data);

        return DB::transaction(function () use ($data, $userId) {
            $fromWarehouse = Warehouse::findOrFail($data['from_warehouse_id']);
            $toWarehouse = Warehouse::findOrFail($data['to_warehouse_id']);

            // Check source warehouse is active
            if (! $fromWarehouse->is_active) {
                throw new \Exception("Source warehouse '{$fromWarehouse->name}' is not active");
            }

            // Check destination warehouse is active
            if (! $toWarehouse->is_active) {
                throw new \Exception("Destination warehouse '{$toWarehouse->name}' is not active");
            }

            // Check sufficient stock in source warehouse
            $fromStock = WarehouseStock::where([
                'warehouse_id' => $fromWarehouse->id,
                'product_id' => $data['product_id'],
            ])->first();

            $availableQty = $fromStock ? $fromStock->quantity : 0;

            if ($availableQty < $data['quantity']) {
                throw new \Exception(
                    "Insufficient stock in '{$fromWarehouse->name}'. ".
                    "Available: {$availableQty}, Requested: {$data['quantity']}"
                );
            }

            // Create transfer record
            $transfer = StockTransfer::create([
                'business_id' => $data['business_id'],
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'user_id' => $userId,
            ]);

            // Audit: created
            $this->logAudit(
                transfer: $transfer,
                action: self::ACTION_CREATED,
                userId: $userId,
                fromStockBefore: $availableQty,
                toStockBefore: $this->getDestinationStock($toWarehouse->id, $data['product_id']),
                notes: 'Transfer created',
                status: 'success'
            );

            return $transfer;
        });
    }

    /**
     * Complete a pending transfer — move stock between warehouses.
     *
     * @throws \Exception if transfer cannot be completed
     */
    public function completeTransfer(StockTransfer $transfer, int $userId): StockTransfer
    {
        if ($transfer->status !== 'pending') {
            throw new \Exception("Transfer #{$transfer->id} is not pending (current status: {$transfer->status})");
        }

        // Re-validate stock availability (could have changed since creation)
        $fromStock = WarehouseStock::where([
            'warehouse_id' => $transfer->from_warehouse_id,
            'product_id' => $transfer->product_id,
        ])->first();

        $fromQtyBefore = $fromStock ? $fromStock->quantity : 0;

        // Log failure BEFORE the transaction so audit persists even on rollback
        if ($fromQtyBefore < $transfer->quantity) {
            $this->logAudit(
                transfer: $transfer,
                action: self::ACTION_FAILED,
                userId: $userId,
                fromStockBefore: $fromQtyBefore,
                toStockBefore: $this->getDestinationStock($transfer->to_warehouse_id, $transfer->product_id),
                notes: "Insufficient stock. Available: {$fromQtyBefore}, Required: {$transfer->quantity}",
                status: 'failed',
                metadata: ['error' => 'insufficient_stock', 'available' => $fromQtyBefore]
            );

            throw new \Exception(
                "Cannot complete transfer #{$transfer->id}: insufficient stock. ".
                "Available: {$fromQtyBefore}, Required: {$transfer->quantity}"
            );
        }

        return DB::transaction(function () use ($transfer, $userId, $fromQtyBefore, $fromStock) {

            $toQtyBefore = $this->getDestinationStock($transfer->to_warehouse_id, $transfer->product_id);

            // Decrement source warehouse stock
            $fromStock->decrement('quantity', $transfer->quantity);
            $fromQtyAfter = $fromStock->fresh()->quantity;

            // Increment destination warehouse stock
            $toStock = WarehouseStock::firstOrCreate(
                [
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'product_id' => $transfer->product_id,
                ],
                [
                    'business_id' => $transfer->business_id,
                    'quantity' => 0,
                ]
            );
            $toStock->increment('quantity', $transfer->quantity);
            $toQtyAfter = $toStock->fresh()->quantity;

            // Update transfer status
            $transfer->update(['status' => 'completed']);

            // Log traceability for batch-level tracking
            TraceabilityLog::create([
                'business_id' => $transfer->business_id,
                'product_id' => $transfer->product_id,
                'from_warehouse_id' => $transfer->from_warehouse_id,
                'to_warehouse_id' => $transfer->to_warehouse_id,
                'type' => 'transfer',
                'quantity' => $transfer->quantity,
                'user_id' => $userId,
                'notes' => "Stock transfer #{$transfer->id} completed",
            ]);

            // Audit: completed
            $this->logAudit(
                transfer: $transfer,
                action: self::ACTION_COMPLETED,
                userId: $userId,
                fromStockBefore: $fromQtyBefore,
                fromStockAfter: $fromQtyAfter,
                toStockBefore: $toQtyBefore,
                toStockAfter: $toQtyAfter,
                notes: $transfer->notes ?? 'Transfer completed',
                status: 'success'
            );

            Log::info("Stock transfer #{$transfer->id} completed", [
                'from_warehouse' => $transfer->from_warehouse_id,
                'to_warehouse' => $transfer->to_warehouse_id,
                'product' => $transfer->product_id,
                'quantity' => $transfer->quantity,
            ]);

            return $transfer->fresh();
        });
    }

    /**
     * Cancel a pending transfer.
     *
     * @throws \Exception if transfer cannot be cancelled
     */
    public function cancelTransfer(StockTransfer $transfer, int $userId, ?string $reason = null): StockTransfer
    {
        if ($transfer->status !== 'pending') {
            throw new \Exception("Transfer #{$transfer->id} cannot be cancelled (current status: {$transfer->status})");
        }

        return DB::transaction(function () use ($transfer, $userId, $reason) {
            $transfer->update(['status' => 'cancelled']);

            $this->logAudit(
                transfer: $transfer,
                action: self::ACTION_CANCELLED,
                userId: $userId,
                notes: $reason ?? 'Transfer cancelled',
                status: 'success'
            );

            return $transfer->fresh();
        });
    }

    /**
     * Get the full audit trail for a transfer.
     */
    public function getAuditTrail(int $transferId): Collection
    {
        return StockTransferAudit::where('stock_transfer_id', $transferId)
            ->with(['user:id,name', 'fromWarehouse:id,name,code', 'toWarehouse:id,name,code', 'product:id,productName'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get transfer summary for a business.
     */
    public function getTransferSummary(int $businessId, array $filters = []): array
    {
        $query = StockTransfer::forBusiness($businessId)
            ->with(['fromWarehouse:id,name,code', 'toWarehouse:id,name,code', 'product:id,productName']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $transfers = $query->latest()->paginate($filters['per_page'] ?? 15);

        return [
            'transfers' => $transfers,
            'stats' => [
                'total' => StockTransfer::forBusiness($businessId)->count(),
                'pending' => StockTransfer::forBusiness($businessId)->pending()->count(),
                'completed' => StockTransfer::forBusiness($businessId)->completed()->count(),
                'cancelled' => StockTransfer::forBusiness($businessId)->cancelled()->count(),
            ],
        ];
    }

    // ── Private Helpers ────────────────────────────────────────────

    private function validateTransferData(array $data): void
    {
        if (empty($data['from_warehouse_id'])) {
            throw new \Exception('Source warehouse is required');
        }

        if (empty($data['to_warehouse_id'])) {
            throw new \Exception('Destination warehouse is required');
        }

        if (empty($data['product_id'])) {
            throw new \Exception('Product is required');
        }

        if (empty($data['quantity']) || $data['quantity'] <= 0) {
            throw new \Exception('Quantity must be greater than 0');
        }

        if ($data['from_warehouse_id'] === $data['to_warehouse_id']) {
            throw new \Exception('Source and destination warehouses must be different');
        }
    }

    private function getDestinationStock(int $warehouseId, int $productId): int
    {
        $stock = WarehouseStock::where([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
        ])->first();

        return $stock ? $stock->quantity : 0;
    }

    private function logAudit(
        StockTransfer $transfer,
        string $action,
        int $userId,
        ?int $fromStockBefore = null,
        ?int $fromStockAfter = null,
        ?int $toStockBefore = null,
        ?int $toStockAfter = null,
        ?string $notes = null,
        string $status = 'success',
        ?array $metadata = null
    ): StockTransferAudit {
        return StockTransferAudit::create([
            'business_id' => $transfer->business_id,
            'stock_transfer_id' => $transfer->id,
            'user_id' => $userId,
            'action' => $action,
            'from_warehouse_id' => $transfer->from_warehouse_id,
            'to_warehouse_id' => $transfer->to_warehouse_id,
            'product_id' => $transfer->product_id,
            'quantity' => $transfer->quantity,
            'from_stock_before' => $fromStockBefore,
            'from_stock_after' => $fromStockAfter,
            'to_stock_before' => $toStockBefore,
            'to_stock_after' => $toStockAfter,
            'notes' => $notes,
            'status' => $status,
            'metadata' => $metadata,
        ]);
    }
}
