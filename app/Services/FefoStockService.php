<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inventory;
use App\Models\StockBatch;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * FefoStockService - First Expiry, First Out Stock Allocation Engine
 *
 * المسؤول عن تخصيص المخزون بناءً على تاريخ انتهاء الصلاحية (FEFO)
 * أقرب تاريخ انتهاء يخرج أولاً
 *
 * Features:
 * - FEFO allocation (nearest expiry first)
 * - Stock reservation for pending operations
 * - Batch-level stock deduction with audit trail
 * - Pessimistic locking for thread safety
 * - Supports both Inventory (UUID) and StockBatch (int) models
 *
 * @version 1.0.0
 */
class FefoStockService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly StockMovementService $stockMovementService,
        private readonly StockBatchService $stockBatchService,
    ) {}

    // ────────────────────────────── FEFO Allocation ──────────────────────────────

    /**
     * Get FEFO-ordered batches for a product at a specific branch.
     * Returns available batches sorted by expiry_date ASC → received_at ASC.
     *
     * @return Collection<Inventory>
     */
    public function getFefoBatches(string $productId, string $branchId): Collection
    {
        return Inventory::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->whereRaw('(quantity - COALESCE(reserved_quantity, 0)) > 0')
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')  // NULL expiry dates last
            ->orderBy('expiry_date', 'asc')
            ->orderBy('received_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Allocate stock from FEFO batches for a required quantity.
     * Returns array of allocations from different batches as needed.
     *
     * @return array<int, array{
     *   inventory_id: string,
     *   product_id: string,
     *   branch_id: string,
     *   batch_number: string|null,
     *   expiry_date: string|null,
     *   quantity: float,
     *   unit_price: float,
     *   cost_price: float,
     * }>
     *
     * @throws \RuntimeException إذا كانت الكمية غير متوفرة
     */
    public function allocate(string $productId, string $branchId, float $quantityNeeded): array
    {
        if ($quantityNeeded <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $batches = $this->getFefoBatches($productId, $branchId);

        if ($batches->isEmpty()) {
            throw new \RuntimeException(
                "No available stock found for product {$productId} at branch {$branchId}."
            );
        }

        $allocations = [];
        $remaining = $quantityNeeded;

        foreach ($batches as $inventory) {
            if ($remaining <= 0) {
                break;
            }

            $available = (float) ($inventory->quantity - ($inventory->reserved_quantity ?? 0));

            if ($available <= 0) {
                continue;
            }

            $take = min($available, $remaining);

            $allocations[] = [
                'inventory_id' => $inventory->id,
                'product_id' => $productId,
                'branch_id' => $branchId,
                'batch_number' => $inventory->batch_number,
                'expiry_date' => $inventory->expiry_date?->toDateString(),
                'quantity' => $take,
                'unit_price' => (float) ($inventory->selling_price ?? 0),
                'cost_price' => (float) ($inventory->cost_price ?? 0),
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            $totalAvailable = $quantityNeeded - $remaining;
            throw new \RuntimeException(
                "Insufficient stock for product {$productId} at branch {$branchId}. " .
                "Needed: {$quantityNeeded}, Available: {$totalAvailable}, Short: {$remaining}"
            );
        }

        return $allocations;
    }

    /**
     * Check if sufficient stock is available via FEFO.
     */
    public function isAvailable(string $productId, string $branchId, float $quantity): bool
    {
        try {
            $this->allocate($productId, $branchId, $quantity);
            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }

    // ────────────────────────────── Reservation ──────────────────────────────

    /**
     * Reserve stock for a pending operation (e.g., sale in progress).
     * Uses pessimistic locking to prevent race conditions.
     */
    public function reserve(array $allocations): void
    {
        $this->db->transaction(function () use ($allocations) {
            foreach ($allocations as $allocation) {
                $inventory = Inventory::where('id', $allocation['inventory_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $newReserved = ($inventory->reserved_quantity ?? 0) + $allocation['quantity'];

                if ($newReserved > $inventory->quantity) {
                    $alreadyReserved = (float) ($inventory->reserved_quantity ?? 0);
                    throw new \RuntimeException(
                        "Cannot reserve {$allocation['quantity']} units. " .
                        "Available: {$inventory->quantity}, Already reserved: {$alreadyReserved}"
                    );
                }

                $inventory->update(['reserved_quantity' => $newReserved]);
            }
        });
    }

    /**
     * Release a reservation (e.g., sale cancelled).
     */
    public function releaseReserve(array $allocations): void
    {
        $this->db->transaction(function () use ($allocations) {
            foreach ($allocations as $allocation) {
                $inventory = Inventory::where('id', $allocation['inventory_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $newReserved = max(0, ($inventory->reserved_quantity ?? 0) - $allocation['quantity']);

                $inventory->update(['reserved_quantity' => $newReserved]);
            }
        });
    }

    // ────────────────────────────── Deduction ──────────────────────────────

    /**
     * Deduct stock (finalize after sale/operation is completed).
     * This reduces quantity and clears reservation.
     */
    public function deduct(array $allocations, string $referenceType, string $referenceId): void
    {
        $this->db->transaction(function () use ($allocations, $referenceType, $referenceId) {
            foreach ($allocations as $allocation) {
                // 1. Deduct from Inventory (branch-level batch)
                $inventory = Inventory::where('id', $allocation['inventory_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $inventory->quantity -= $allocation['quantity'];
                $inventory->reserved_quantity = max(0, ($inventory->reserved_quantity ?? 0) - $allocation['quantity']);
                $inventory->last_moved_at = now();

                if ($inventory->quantity <= 0) {
                    $inventory->quantity = 0;
                    $inventory->status = 'exhausted';
                }

                $inventory->save();

                // 2. Deduct from StockBatch (global batch master)
                if ($allocation['batch_number']) {
                    StockBatch::where('batch_number', $allocation['batch_number'])
                        ->where('product_id', $allocation['product_id'])
                        ->where('status', 'active')
                        ->first()
                        ?->deductQuantity($allocation['quantity']);
                }

                // 3. Log movement
                $this->stockMovementService->logMovement(
                    productId: $allocation['product_id'],
                    branchId: $allocation['branch_id'],
                    movementType: 'out',
                    quantity: $allocation['quantity'],
                    referenceType: $referenceType,
                    referenceId: $referenceId,
                    batchNumber: $allocation['batch_number'],
                    extra: [
                        'inventory_id' => $allocation['inventory_id'],
                        'unit_price' => $allocation['unit_price'],
                        'cost_price' => $allocation['cost_price'],
                    ]
                );
            }
        });
    }

    /**
     * Restore stock to specific batches (for returns/reversals).
     */
    public function restore(array $allocations, string $referenceType, string $referenceId): void
    {
        $this->db->transaction(function () use ($allocations, $referenceType, $referenceId) {
            foreach ($allocations as $allocation) {
                // 1. Restore to Inventory (branch-level batch)
                $inventory = Inventory::where('id', $allocation['inventory_id'])
                    ->lockForUpdate()
                    ->first();

                if ($inventory) {
                    $inventory->quantity += $allocation['quantity'];
                    $inventory->last_moved_at = now();

                    if ($inventory->status === 'exhausted') {
                        $inventory->status = 'available';
                    }

                    $inventory->save();
                } else {
                    // Create new inventory record if batch was fully exhausted
                    $inventory = Inventory::create([
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'company_id' => $allocation['company_id'] ?? app('tenant.company_id'),
                        'branch_id' => $allocation['branch_id'],
                        'product_id' => $allocation['product_id'],
                        'batch_number' => $allocation['batch_number'],
                        'expiry_date' => $allocation['expiry_date'] ?? null,
                        'quantity' => $allocation['quantity'],
                        'reserved_quantity' => 0,
                        'cost_price' => $allocation['cost_price'] ?? 0,
                        'selling_price' => $allocation['unit_price'] ?? 0,
                        'status' => 'available',
                        'received_at' => now(),
                        'last_moved_at' => now(),
                    ]);
                }

                // 2. Restore to StockBatch (global batch master)
                if ($allocation['batch_number']) {
                    StockBatch::where('batch_number', $allocation['batch_number'])
                        ->where('product_id', $allocation['product_id'])
                        ->first()
                        ?->addQuantity($allocation['quantity']);
                }

                // 3. Log movement
                $this->stockMovementService->logMovement(
                    productId: $allocation['product_id'],
                    branchId: $allocation['branch_id'],
                    movementType: 'in',
                    quantity: $allocation['quantity'],
                    referenceType: $referenceType,
                    referenceId: $referenceId,
                    batchNumber: $allocation['batch_number'],
                    extra: [
                        'inventory_id' => $inventory->id,
                        'restored' => true,
                    ]
                );
            }
        });
    }

    // ────────────────────────────── Stock Overview ──────────────────────────────

    /**
     * Get complete stock overview for a product at a branch.
     */
    public function getStockOverview(string $productId, string $branchId): array
    {
        $inventories = $this->getFefoBatches($productId, $branchId);

        $totalQuantity = (float) $inventories->sum('quantity');
        $totalReserved = (float) $inventories->sum('reserved_quantity');
        $totalAvailable = $totalQuantity - $totalReserved;

        return [
            'product_id' => $productId,
            'branch_id' => $branchId,
            'total_quantity' => $totalQuantity,
            'total_reserved' => $totalReserved,
            'total_available' => $totalAvailable,
            'batch_count' => $inventories->count(),
            'has_expired_batches' => Inventory::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->where('quantity', '>', 0)
                ->where('expiry_date', '<', now()->toDateString())
                ->exists(),
            'batches' => $inventories->map(fn(Inventory $inv) => [
                'id' => $inv->id,
                'batch_number' => $inv->batch_number,
                'expiry_date' => $inv->expiry_date?->toDateString(),
                'quantity' => (float) $inv->quantity,
                'reserved' => (float) ($inv->reserved_quantity ?? 0),
                'available' => (float) ($inv->quantity - ($inv->reserved_quantity ?? 0)),
                'cost_price' => (float) ($inv->cost_price ?? 0),
                'selling_price' => (float) ($inv->selling_price ?? 0),
                'rack_location' => $inv->rack_location,
                'status' => $inv->status,
                'days_to_expiry' => $inv->expiry_date ? now()->diffInDays($inv->expiry_date, false) : null,
                'is_expired' => $inv->expiry_date ? $inv->expiry_date->isPast() : false,
            ]),
        ];
    }

    /**
     * Get FEFO priority-sorted batches with available quantities.
     * Useful for POS display and warehouse picking.
     */
    public function getFefoPriorityList(string $productId, string $branchId): array
    {
        $batches = $this->getFefoBatches($productId, $branchId);

        return $batches->map(fn(Inventory $inv, int $index) => [
            'priority' => $index + 1,
            'inventory_id' => $inv->id,
            'batch_number' => $inv->batch_number,
            'expiry_date' => $inv->expiry_date?->toDateString(),
            'available_quantity' => (float) ($inv->quantity - ($inv->reserved_quantity ?? 0)),
            'unit_price' => (float) ($inv->selling_price ?? 0),
            'cost_price' => (float) ($inv->cost_price ?? 0),
            'rack_location' => $inv->rack_location,
        ])->values()->toArray();
    }

    // ────────────────────────────── Expiry Management ──────────────────────────────

    /**
     * Get all batches expiring within a given number of days.
     */
    public function getExpiringBatches(string $companyId, int $days = 30, ?string $branchId = null): Collection
    {
        $query = Inventory::where('company_id', $companyId)
            ->where('quantity', '>', 0)
            ->whereBetween('expiry_date', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ])
            ->with(['product:id,name,sku,barcode', 'branch:id,name']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('expiry_date', 'asc')->get();
    }

    /**
     * Get expired batches that still have stock.
     */
    public function getExpiredBatches(string $companyId, ?string $branchId = null): Collection
    {
        $query = Inventory::where('company_id', $companyId)
            ->where('quantity', '>', 0)
            ->where('expiry_date', '<', now()->toDateString())
            ->with(['product:id,name,sku,barcode', 'branch:id,name']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('expiry_date', 'asc')->get();
    }

    /**
     * Get low stock alerts using FEFO-aware quantities.
     */
    public function getLowStockAlerts(string $companyId, ?string $branchId = null): Collection
    {
        $query = \App\Models\Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('reorder_point', '>', 0);

        if ($branchId) {
            $query->whereHas('inventory', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        return $query->withCount(['inventory as total_available' => function ($q) use ($branchId) {
            $q->select(DB::raw('COALESCE(SUM(quantity - COALESCE(reserved_quantity, 0)), 0)'));
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        }])->havingRaw('total_available <= products.reorder_point')->get();
    }
}

