<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

/**
 * StockMovementService
 *
 * Centralized service for logging all stock movements across the application.
 * Integrates with StockTransferService, PurchaseService, SaleService, etc.
 *
 * @version 1.0.0
 */
class StockMovementService
{
    /**
     * Log a stock movement.
     *
     * @param int $productId
     * @param int $branchId
     * @param string $movementType 'in' | 'out' | 'adjustment'
     * @param float $quantity
     * @param string $referenceType e.g., 'stock_transfer', 'purchase', 'sale', 'return', 'adjustment'
     * @param int|null $referenceId The ID of the reference record
     * @param string|null $batchNumber
     * @param array $extra meta data
     */
    public function logMovement(
        int $productId,
        int $branchId,
        string $movementType,
        float $quantity,
        string $referenceType,
        ?int $referenceId = null,
        ?string $batchNumber = null,
        array $extra = []
    ): ActivityLog {
        $companyId = app()->bound('tenant.company_id')
            ? app('tenant.company_id')
            : (auth()->check() ? auth()->user()->company_id : null);

        $description = sprintf(
            'Stock %s: %s units of product #%d at branch #%d (Batch: %s) [%s:%s]',
            $movementType === 'in' ? 'IN' : ($movementType === 'out' ? 'OUT' : 'ADJUSTMENT'),
            $quantity,
            $productId,
            $branchId,
            $batchNumber ?? 'N/A',
            $referenceType,
            $referenceId ?? 'manual'
        );

        return ActivityLog::create([
            'company_id' => $companyId,
            'user_id' => auth()->id(),
            'action' => "stock_movement.{$movementType}",
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'performed_at' => now(),
            'properties' => array_merge($extra, [
                'product_id' => $productId,
                'branch_id' => $branchId,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'batch_number' => $batchNumber,
            ]),
        ]);
    }

    /**
     * Get movement history for a product in a branch.
     */
    public function getMovementHistory(int $productId, int $branchId, int $limit = 50): array
    {
        return ActivityLog::where('company_id', app('tenant.company_id'))
            ->where('action', 'LIKE', 'stock_movement.%')
            ->where('properties->product_id', $productId)
            ->where('properties->branch_id', $branchId)
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get summary of stock movements for a company.
     */
    public function getMovementSummary(int $companyId, string $period = 'month'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $logs = ActivityLog::where('company_id', $companyId)
            ->where('action', 'LIKE', 'stock_movement.%')
            ->where('performed_at', '>=', $startDate)
            ->get();

        return [
            'total_movements' => $logs->count(),
            'total_in' => $logs->where('action', 'stock_movement.in')->sum(function ($log) {
                $props = $log->properties;
                return $props['quantity'] ?? 0;
            }),
            'total_out' => $logs->where('action', 'stock_movement.out')->sum(function ($log) {
                $props = $log->properties;
                return $props['quantity'] ?? 0;
            }),
            'total_adjustments' => $logs->where('action', 'stock_movement.adjustment')->count(),
            'by_reference_type' => $logs->groupBy(function ($log) {
                $props = $log->properties;
                return $props['reference_type'] ?? 'unknown';
            })->map(function ($group) {
                return $group->count();
            }),
        ];
    }
}