<?php

namespace App\Services;

use App\Models\BatchLot;
use App\Models\RecallEvent;
use App\Models\TraceabilityLog;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class TraceabilityService
{
    /**
     * Create batch/lot record
     */
    public function createBatchLot(array $data): BatchLot
    {
        return BatchLot::create($data);
    }

    /**
     * Update batch/lot record
     */
    public function updateBatchLot(BatchLot $batchLot, array $data): BatchLot
    {
        $batchLot->update($data);

        return $batchLot->fresh();
    }

    /**
     * Initiate recall event
     */
    public function initiateRecall(array $data): RecallEvent
    {
        return DB::transaction(function () use ($data) {
            $recall = RecallEvent::create([
                'business_id' => $data['business_id'],
                'product_id' => $data['product_id'] ?? null,
                'batch_lot_number' => $data['batch_lot_number'] ?? null,
                'reason' => $data['reason'],
                'initiated_at' => now(),
                'status' => 'active',
                'description' => $data['description'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            // Mark batch as recalled if batch_lot_number is provided
            if ($data['batch_lot_number']) {
                BatchLot::where('batch_number', $data['batch_lot_number'])
                    ->orWhere('lot_number', $data['batch_lot_number'])
                    ->update(['recall_date' => now()]);
            }

            return $recall;
        });
    }

    /**
     * Resolve recall event
     */
    public function resolveRecall(RecallEvent $recall): RecallEvent
    {
        $recall->resolve();

        return $recall->fresh();
    }

    /**
     * Log traceability event
     */
    public function logTraceability(array $data): TraceabilityLog
    {
        return TraceabilityLog::create($data);
    }

    /**
     * Get complete traceability chain for a product
     */
    public function getProductTraceability(int $businessId, int $productId, ?string $batchLotNumber = null): array
    {
        $query = TraceabilityLog::forBusiness($businessId)
            ->forProduct($productId)
            ->with(['fromWarehouse', 'toWarehouse', 'user'])
            ->orderBy('created_at');

        if ($batchLotNumber) {
            $query->forBatchLot($batchLotNumber);
        }

        $logs = $query->get();

        return [
            'product_id' => $productId,
            'batch_lot_number' => $batchLotNumber,
            'total_movements' => $logs->count(),
            'movements' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->type,
                    'type_label' => $log->type_label,
                    'quantity' => $log->quantity,
                    'movement_path' => $log->movement_path,
                    'from_warehouse' => $log->fromWarehouse?->name,
                    'to_warehouse' => $log->toWarehouse?->name,
                    'user' => $log->user?->name,
                    'notes' => $log->notes,
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get batch/lot information
     */
    public function getBatchLotInfo(int $businessId, string $identifier): ?BatchLot
    {
        return BatchLot::forBusiness($businessId)
            ->where('batch_number', $identifier)
            ->orWhere('lot_number', $identifier)
            ->with('product')
            ->first();
    }

    /**
     * Get affected products for a recall
     */
    public function getAffectedProductsForRecall(int $businessId, RecallEvent $recall): array
    {
        $query = BatchLot::forBusiness($businessId);

        if ($recall->product_id) {
            $query->where('product_id', $recall->product_id);
        }

        if ($recall->batch_lot_number) {
            $query->where('batch_number', $recall->batch_lot_number)
                ->orWhere('lot_number', $recall->batch_lot_number);
        }

        $batches = $query->with('product')->get();

        return [
            'recall_id' => $recall->id,
            'total_affected_batches' => $batches->count(),
            'batches' => $batches->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'identifier' => $batch->identifier,
                    'batch_number' => $batch->batch_number,
                    'lot_number' => $batch->lot_number,
                    'product_name' => $batch->product?->name,
                    'expiry_date' => $batch->expiry_date?->toIso8601String(),
                    'is_expired' => $batch->isExpired(),
                    'supplier_name' => $batch->supplier_name,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get traceability statistics
     */
    public function getTraceabilityStatistics(int $businessId, array $filters = []): array
    {
        $query = TraceabilityLog::forBusiness($businessId);

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        $logs = $query->get();

        return [
            'total_movements' => $logs->count(),
            'total_quantity_moved' => $logs->sum('quantity'),
            'movements_by_type' => [
                'transfers' => $logs->where('type', 'transfer')->count(),
                'sales' => $logs->where('type', 'sale')->count(),
                'purchases' => $logs->where('type', 'purchase')->count(),
                'adjustments' => $logs->where('type', 'adjustment')->count(),
                'recalls' => $logs->where('type', 'recall')->count(),
            ],
            'most_active_warehouses' => $this->getMostActiveWarehouses($logs),
        ];
    }

    /**
     * Get recall statistics
     */
    public function getRecallStatistics(int $businessId, array $filters = []): array
    {
        $query = RecallEvent::forBusiness($businessId);

        if (isset($filters['date_from'])) {
            $query->where('initiated_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('initiated_at', '<=', $filters['date_to']);
        }

        $recalls = $query->get();

        return [
            'total_recalls' => $recalls->count(),
            'active_recalls' => $recalls->where('status', 'active')->count(),
            'resolved_recalls' => $recalls->where('status', 'resolved')->count(),
            'average_resolution_days' => $this->calculateAverageResolutionDays($recalls),
            'recalls_by_reason' => $recalls->groupBy('reason')->map->count(),
        ];
    }

    /**
     * Get expiring batches
     */
    public function getExpiringBatches(int $businessId, int $days = 30): array
    {
        $batches = BatchLot::forBusiness($businessId)
            ->with('product')
            ->expiringSoon()
            ->orderBy('expiry_date')
            ->get();

        return [
            'total_expiring_soon' => $batches->count(),
            'batches' => $batches->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'identifier' => $batch->identifier,
                    'product_name' => $batch->product?->name,
                    'expiry_date' => $batch->expiry_date?->toIso8601String(),
                    'days_until_expiry' => $batch->days_until_expiry,
                    'batch_number' => $batch->batch_number,
                    'lot_number' => $batch->lot_number,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get expired batches
     */
    public function getExpiredBatches(int $businessId): array
    {
        $batches = BatchLot::forBusiness($businessId)
            ->with('product')
            ->expired()
            ->orderBy('expiry_date', 'desc')
            ->get();

        return [
            'total_expired' => $batches->count(),
            'batches' => $batches->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'identifier' => $batch->identifier,
                    'product_name' => $batch->product?->name,
                    'expiry_date' => $batch->expiry_date?->toIso8601String(),
                    'days_since_expiry' => abs($batch->days_until_expiry),
                    'batch_number' => $batch->batch_number,
                    'lot_number' => $batch->lot_number,
                    'is_recalled' => $batch->isRecalled(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Calculate average resolution days for recalls
     */
    protected function calculateAverageResolutionDays($recalls): float
    {
        $resolvedRecalls = $recalls->filter(function ($recall) {
            return $recall->resolved_at !== null;
        });

        if ($resolvedRecalls->isEmpty()) {
            return 0;
        }

        $totalDays = $resolvedRecalls->sum(function ($recall) {
            return $recall->duration_days;
        });

        return round($totalDays / $resolvedRecalls->count(), 1);
    }

    /**
     * Get most active warehouses from traceability logs
     */
    protected function getMostActiveWarehouses($logs): array
    {
        $warehouseActivity = [];

        foreach ($logs as $log) {
            if ($log->from_warehouse_id) {
                $warehouseActivity[$log->from_warehouse_id] = ($warehouseActivity[$log->from_warehouse_id] ?? 0) + 1;
            }
            if ($log->to_warehouse_id) {
                $warehouseActivity[$log->to_warehouse_id] = ($warehouseActivity[$log->to_warehouse_id] ?? 0) + 1;
            }
        }

        arsort($warehouseActivity);

        $topWarehouses = array_slice(array_keys($warehouseActivity), 0, 5, true);
        $warehouseData = Warehouse::whereIn('id', $topWarehouses)
            ->get()
            ->keyBy('id');

        return collect($topWarehouses)->map(function ($warehouseId) use ($warehouseData, $warehouseActivity) {
            return [
                'warehouse_id' => $warehouseId,
                'warehouse_name' => $warehouseData[$warehouseId]?->name ?? 'Unknown',
                'activity_count' => $warehouseActivity[$warehouseId],
            ];
        })->values()->toArray();
    }
}
