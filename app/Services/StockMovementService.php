<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

/**
 * StockMovementService
 *
 * Centralized service for logging all stock movements across the application.
 * Uses the dedicated `stock_movements` table (UUID) for full audit trail.
 *
 * Integrates with:
 * - SaleService (out: sale)
 * - PurchaseOrderService / GoodsReceivedNoteService (in: purchase_receipt)
 * - StockTransferService (out/in: stock_transfer)
 * - StockAdjustmentService (adjustment)
 * - SaleReturnService (in: sale_return)
 * - PurchaseReturnService (out: purchase_return)
 *
 * @version 2.0.0
 */
class StockMovementService
{
    /**
     * Log a stock movement to the stock_movements table.
     *
     * @param string $productId UUID of the product
     * @param string $branchId UUID of the branch
     * @param string $movementType 'in' | 'out' | 'adjustment'
     * @param float $quantity Quantity moved
     * @param string|null $referenceType e.g., 'sale', 'purchase_receipt', 'stock_transfer', 'sale_return', 'purchase_return', 'adjustment'
     * @param string|null $referenceId UUID of the reference record
     * @param string|null $batchNumber Batch number involved
     * @param array $extra Additional metadata
     *
     * @return StockMovement
     */
    public function logMovement(
        string $productId,
        string $branchId,
        string $movementType,
        float $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $batchNumber = null,
        array $extra = []
    ): StockMovement {
        $companyId = $this->resolveCompanyId();
        $performedBy = Auth::id();

        // Calculate quantity_before and quantity_after from inventory
        $inventoryRecord = \App\Models\Inventory::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->when($batchNumber, fn($q) => $q->where('batch_number', $batchNumber))
            ->first();

        $quantityBefore = 0;
        $quantityAfter = 0;

        if ($inventoryRecord) {
            $quantityBefore = (float) $inventoryRecord->quantity;
            $quantityAfter = match ($movementType) {
                'in' => $quantityBefore + $quantity,
                'out' => $quantityBefore - $quantity,
                default => $quantityBefore,
            };
        }

        $costPrice = (float) ($extra['cost_price'] ?? 0);
        $unitCost = (float) ($extra['unit_cost'] ?? $costPrice);
        $totalCost = $unitCost * $quantity;

        return StockMovement::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'product_id' => $productId,
            'batch_number' => $batchNumber,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'cost_price' => $costPrice,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'from_branch_id' => $extra['from_branch_id'] ?? null,
            'to_branch_id' => $extra['to_branch_id'] ?? null,
            'notes' => $extra['notes'] ?? null,
            'performed_by' => $performedBy,
            'performed_at' => now(),
        ]);
    }

    /**
     * Log an "in" movement (stock received/added).
     */
    public function logStockIn(
        string $productId,
        string $branchId,
        float $quantity,
        string $referenceType,
        string $referenceId,
        ?string $batchNumber = null,
        array $extra = []
    ): StockMovement {
        return $this->logMovement(
            $productId, $branchId, 'in', $quantity,
            $referenceType, $referenceId, $batchNumber, $extra
        );
    }

    /**
     * Log an "out" movement (stock deducted/sold/transferred).
     */
    public function logStockOut(
        string $productId,
        string $branchId,
        float $quantity,
        string $referenceType,
        string $referenceId,
        ?string $batchNumber = null,
        array $extra = []
    ): StockMovement {
        return $this->logMovement(
            $productId, $branchId, 'out', $quantity,
            $referenceType, $referenceId, $batchNumber, $extra
        );
    }

    /**
     * Log an adjustment movement.
     */
    public function logAdjustment(
        string $productId,
        string $branchId,
        float $quantity,
        string $referenceType,
        string $referenceId,
        ?string $batchNumber = null,
        ?string $notes = null
    ): StockMovement {
        return $this->logMovement(
            $productId, $branchId, 'adjustment', $quantity,
            $referenceType, $referenceId, $batchNumber,
            ['notes' => $notes]
        );
    }

    /**
     * Get movement history for a product in a branch.
     *
     * @return \Illuminate\Support\Collection<StockMovement>
     */
    public function getMovementHistory(
        string $productId,
        string $branchId,
        int $limit = 50,
        ?string $movementType = null
    ) {
        $query = StockMovement::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->with(['performedBy:id,name']);

        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        return $query->latestFirst()->limit($limit)->get();
    }

    /**
     * Get movement history within a date range.
     */
    public function getMovementHistoryByDateRange(
        string $companyId,
        string $startDate,
        string $endDate,
        ?string $productId = null,
        ?string $branchId = null,
        ?string $movementType = null
    ) {
        $query = StockMovement::where('company_id', $companyId)
            ->with(['product:id,name,sku,barcode', 'performedBy:id,name']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        return $query->whereBetween('performed_at', [$startDate, $endDate])
            ->latestFirst()
            ->get();
    }

    /**
     * Get movement summary for a company over a period.
     */
    public function getMovementSummary(string $companyId, string $period = 'month'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $movements = StockMovement::where('company_id', $companyId)
            ->where('performed_at', '>=', $startDate)
            ->get();

        return [
            'total_movements' => $movements->count(),
            'total_in' => (float) $movements->where('movement_type', 'in')->sum('quantity'),
            'total_out' => (float) $movements->where('movement_type', 'out')->sum('quantity'),
            'total_adjustments' => $movements->where('movement_type', 'adjustment')->count(),
            'net_change' => (float) (
                $movements->where('movement_type', 'in')->sum('quantity')
                - $movements->where('movement_type', 'out')->sum('quantity')
            ),
            'by_reference_type' => $movements
                ->groupBy(fn($m) => $m->reference_type ?? 'manual')
                ->map(fn($group) => [
                    'count' => $group->count(),
                    'quantity' => (float) $group->sum('quantity'),
                ]),
        ];
    }

    /**
     * Resolve the current company ID.
     */
    private function resolveCompanyId(): string
    {
        if (app()->bound('tenant.company_id')) {
            return app('tenant.company_id');
        }

        if (Auth::check()) {
            return Auth::user()->company_id;
        }

        throw new \RuntimeException('Cannot resolve company_id for stock movement.');
    }
}

