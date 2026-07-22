<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockBatch;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * StockBatchService
 *
 * Service for managing batch-level inventory operations.
 * Provides FEFO (First Expiry, First Out) allocation, stock reservation,
 * expiry tracking, and batch-level stock movements.
 *
 * Integrates with both:
 * - StockBatch (auto-increment PK, batch master table)
 * - Inventory (UUID PK, branch-level batch tracking)
 *
 * @version 1.0.0
 */
class StockBatchService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly StockMovementService $stockMovementService,
    ) {}

    // ────────────────────────────── FEFO Allocation ──────────────────────────────

    /**
     * Get FEFO-ordered batches for a product at a specific branch.
     * Returns batches ordered by expiry_date ascending, then received_at ascending.
     *
     * @return Collection<Inventory>
     */
    public function getFefoBatches(string $productId, string $branchId, float $quantityNeeded = 0): Collection
    {
        $query = Inventory::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('status', 'available')
            ->whereColumn('quantity', '>', 'reserved_quantity')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('received_at', 'asc');

        if ($quantityNeeded > 0) {
            // Only fetch batches that have available stock
            $query->whereRaw('(quantity - reserved_quantity) > 0');
        }

        return $query->get();
    }

    /**
     * Allocate stock from FEFO batches for a required quantity.
     * Returns an array of allocations: [{inventory_id, batch_number, quantity, unit_price}]
     *
     * @throws \RuntimeException if insufficient stock
     */
    public function allocateFefo(string $productId, string $branchId, float $quantityNeeded): array
    {
        if ($quantityNeeded <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $batches = $this->getFefoBatches($productId, $branchId);

        $allocations = [];
        $remaining = $quantityNeeded;

        foreach ($batches as $inventory) {
            if ($remaining <= 0) {
                break;
            }

            $available = $inventory->quantity - $inventory->reserved_quantity;

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
                'unit_price' => $inventory->selling_price,
                'cost_price' => $inventory->cost_price,
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \RuntimeException(
                "Insufficient stock for product {$productId} at branch {$branchId}. " .
                "Needed: {$quantityNeeded}, Available: " . ($quantityNeeded - $remaining)
            );
        }

        return $allocations;
    }

    /**
     * Reserve stock from specific inventory records (before confirming sale).
     */
    public function reserveStock(array $allocations): void
    {
        $this->db->transaction(function () use ($allocations) {
            foreach ($allocations as $allocation) {
                $inventory = Inventory::findOrFail($allocation['inventory_id']);
                $inventory->reserve($allocation['quantity']);

                // Also update the corresponding StockBatch record if it exists
                if (! empty($allocation['batch_number'])) {
                    StockBatch::where('batch_number', $allocation['batch_number'])
                        ->where('product_id', $allocation['product_id'])
                        ->where('status', 'active')
                        ->first()
                        ?->deductQuantity($allocation['quantity']);
                }
            }
        });
    }

    /**
     * Confirm stock deduction (finalize after sale is completed).
     */
    public function confirmDeduction(array $allocations): void
    {
        $this->db->transaction(function () use ($allocations) {
            foreach ($allocations as $allocation) {
                $inventory = Inventory::findOrFail($allocation['inventory_id']);

                // Release the reserve and deduct actual quantity
                $inventory->releaseReserve($allocation['quantity']);
                $inventory->deductQuantity($allocation['quantity']);

            // Log the stock movement
                $this->stockMovementService->logMovement(
                    productId: $allocation['product_id'],
                    branchId: $allocation['branch_id'],
                    movementType: 'out',
                    quantity: $allocation['quantity'],
                    referenceType: 'sale',
                    referenceId: $allocation['reference_id'] ?? null,
                    batchNumber: $allocation['batch_number'],
                    extra: [
                        'inventory_id' => $allocation['inventory_id'],
                        'unit_price' => $allocation['unit_price'],
                    ]
                );

                // Set last_moved_at timestamp
                $inventory->update(['last_moved_at' => now()]);
            }
        });
    }

    /**
     * Release reserved stock (e.g., if sale is cancelled).
     */
    public function releaseReservation(array $allocations): void
    {
        $this->db->transaction(function () use ($allocations) {
            foreach ($allocations as $allocation) {
                $inventory = Inventory::findOrFail($allocation['inventory_id']);
                $inventory->releaseReserve($allocation['quantity']);

                // Restore StockBatch if it was deducted
                if (! empty($allocation['batch_number'])) {
                    StockBatch::where('batch_number', $allocation['batch_number'])
                        ->where('product_id', $allocation['product_id'])
                        ->first()
                        ?->addQuantity($allocation['quantity']);
                }
            }
        });
    }

    // ────────────────────────────── Receiving Stock ──────────────────────────────

    /**
     * Record new stock receipt (from GRN / purchase order).
     * Creates both StockBatch (batch master) and Inventory (branch-level) records.
     *
     * @return array{stock_batch: StockBatch|null, inventory: Inventory}
     */
    public function receiveStock(array $data): array
    {
        return $this->db->transaction(function () use ($data) {
            // 1. Find or create the StockBatch (batch master)
            $stockBatch = StockBatch::firstOrCreate(
                [
                    'company_id' => $data['company_id'],
                    'product_id' => $data['product_id'],
                    'batch_number' => $data['batch_number'] ?? 'BATCH-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    'supplier_id' => $data['supplier_id'] ?? null,
                ],
                [
                    'branch_id' => $data['branch_id'] ?? null,
                    'expiry_date' => $data['expiry_date'] ?? null,
                    'cost_price' => $data['cost_price'] ?? 0,
                    'purchase_price' => $data['purchase_price'] ?? $data['cost_price'] ?? 0,
                    'quantity_available' => 0,
                    'status' => 'active',
                ]
            );

            // If batch already exists, increase quantity
            $stockBatch->addQuantity($data['quantity']);

            // 2. Create the Inventory record (branch-level batch tracking)
            $inventory = Inventory::create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'product_id' => $data['product_id'],
                'batch_number' => $stockBatch->batch_number,
                'expiry_date' => $data['expiry_date'] ?? null,
                'manufacturing_date' => $data['manufacturing_date'] ?? null,
                'quantity' => $data['quantity'],
                'reserved_quantity' => 0,
                'cost_price' => $data['cost_price'] ?? 0,
                'selling_price' => $data['selling_price'] ?? 0,
                'rack_location' => $data['rack_location'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'grn_id' => $data['grn_id'] ?? null,
                'status' => 'available',
                'received_at' => now(),
                'last_moved_at' => now(),
            ]);

            // 3. Log the stock movement
            $this->stockMovementService->logMovement(
                productId: $data['product_id'],
                branchId: $data['branch_id'],
                movementType: 'in',
                quantity: $data['quantity'],
                referenceType: 'purchase_receipt',
                referenceId: $data['grn_id'] ?? $data['purchase_order_id'] ?? null,
                batchNumber: $stockBatch->batch_number,
                extra: [
                    'inventory_id' => $inventory->id,
                    'cost_price' => $data['cost_price'] ?? 0,
                ]
            );

            return [
                'stock_batch' => $stockBatch,
                'inventory' => $inventory,
            ];
        });
    }

    // ────────────────────────────── Expiry Tracking ──────────────────────────────

    /**
     * Get batches that are expiring within a given number of days.
     *
     * @return Collection<Inventory>
     */
    public function getExpiringBatches(string $companyId, int $days = 30): Collection
    {
        return Inventory::where('company_id', $companyId)
            ->where('status', 'available')
            ->whereColumn('quantity', '>', 'reserved_quantity')
            ->whereBetween('expiry_date', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ])
            ->with(['product:id,name,sku,barcode', 'branch:id,name'])
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get batches that are already expired.
     *
     * @return Collection<Inventory>
     */
    public function getExpiredBatches(string $companyId): Collection
    {
        return Inventory::where('company_id', $companyId)
            ->where('expiry_date', '<', now()->toDateString())
            ->where('quantity', '>', 0)
            ->with(['product:id,name,sku,barcode', 'branch:id,name'])
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get low stock alerts (products where total available is below reorder point).
     *
     * @return Collection
     */
    public function getLowStockAlerts(string $companyId): Collection
    {
        return Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('track_inventory', true)
            ->whereHas('inventory', function ($q) {
                $q->selectRaw('SUM(quantity - reserved_quantity) as total_available')
                    ->havingRaw('COALESCE(total_available, 0) <= products.reorder_point');
            })
            ->withCount(['inventory as total_stock' => function ($q) {
                $q->select(DB::raw('COALESCE(SUM(quantity - reserved_quantity), 0)'));
            }])
            ->get();
    }

    /**
     * Get stock overview for a product at a specific branch.
     */
    public function getProductStockOverview(string $productId, string $branchId): array
    {
        $inventories = Inventory::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('status', 'available')
            ->orderBy('expiry_date', 'asc')
            ->get();

        $totalQuantity = $inventories->sum('quantity');
        $totalReserved = $inventories->sum('reserved_quantity');
        $totalAvailable = $totalQuantity - $totalReserved;

        return [
            'product_id' => $productId,
            'branch_id' => $branchId,
            'total_quantity' => (float) $totalQuantity,
            'total_reserved' => (float) $totalReserved,
            'total_available' => (float) $totalAvailable,
            'batch_count' => $inventories->count(),
            'batches' => $inventories->map(fn (Inventory $inv) => [
                'id' => $inv->id,
                'batch_number' => $inv->batch_number,
                'expiry_date' => $inv->expiry_date?->toDateString(),
                'quantity' => (float) $inv->quantity,
                'reserved' => (float) $inv->reserved_quantity,
                'available' => $inv->available_quantity,
                'cost_price' => (float) $inv->cost_price,
                'selling_price' => (float) $inv->selling_price,
                'rack_location' => $inv->rack_location,
                'status' => $inv->status,
                'days_to_expiry' => $inv->expiry_date ? now()->diffInDays($inv->expiry_date, false) : null,
            ]),
        ];
    }

    /**
     * Transfer stock between branches at batch level.
     */
    public function transferBetweenBranches(
        string $inventoryId,
        string $fromBranchId,
        string $toBranchId,
        float $quantity,
        ?string $notes = null
    ): array {
        return $this->db->transaction(function () use ($inventoryId, $fromBranchId, $toBranchId, $quantity, $notes) {
            $sourceInventory = Inventory::where('id', $inventoryId)
                ->where('branch_id', $fromBranchId)
                ->firstOrFail();

            if ($sourceInventory->available_quantity < $quantity) {
                throw new \RuntimeException(
                    "Insufficient available stock. " .
                    "Available: {$sourceInventory->available_quantity}, Required: {$quantity}"
                );
            }

            // Deduct from source branch
            $sourceInventory->deductQuantity($quantity);

            // Create or update inventory at destination branch
            $destInventory = Inventory::where('product_id', $sourceInventory->product_id)
                ->where('branch_id', $toBranchId)
                ->where('batch_number', $sourceInventory->batch_number)
                ->where('expiry_date', $sourceInventory->expiry_date)
                ->where('status', 'available')
                ->first();

            if ($destInventory) {
                $destInventory->addQuantity($quantity);
            } else {
                $destInventory = Inventory::create([
                    'company_id' => $sourceInventory->company_id,
                    'branch_id' => $toBranchId,
                    'product_id' => $sourceInventory->product_id,
                    'batch_number' => $sourceInventory->batch_number,
                    'expiry_date' => $sourceInventory->expiry_date,
                    'manufacturing_date' => $sourceInventory->manufacturing_date,
                    'quantity' => $quantity,
                    'reserved_quantity' => 0,
                    'cost_price' => $sourceInventory->cost_price,
                    'selling_price' => $sourceInventory->selling_price,
                    'rack_location' => $sourceInventory->rack_location,
                    'status' => 'available',
                    'received_at' => now(),
                    'last_moved_at' => now(),
                ]);
            }

            // Log movements
            $this->stockMovementService->logMovement(
                productId: $sourceInventory->product_id,
                branchId: $fromBranchId,
                movementType: 'out',
                quantity: $quantity,
                referenceType: 'stock_transfer',
                referenceId: null,
                batchNumber: $sourceInventory->batch_number,
                extra: ['to_branch_id' => $toBranchId, 'notes' => $notes]
            );

            $this->stockMovementService->logMovement(
                productId: $sourceInventory->product_id,
                branchId: $toBranchId,
                movementType: 'in',
                quantity: $quantity,
                referenceType: 'stock_transfer',
                referenceId: null,
                batchNumber: $destInventory->batch_number,
                extra: ['from_branch_id' => $fromBranchId, 'notes' => $notes]
            );

            return [
                'source' => $sourceInventory->fresh(),
                'destination' => $destInventory->fresh(),
            ];
        });
    }
}

