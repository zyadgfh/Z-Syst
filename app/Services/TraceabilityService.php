<?php

namespace App\Services;

use App\Jobs\NotifyRecallAffectedCustomers;
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
     * Initiate recall event with affected batch detection
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

            // Detect and link affected batches
            $affectedBatches = $this->detectAffectedBatches(
                $data['business_id'],
                $data['product_id'] ?? null,
                $data['batch_lot_number'] ?? null
            );

            foreach ($affectedBatches as $batch) {
                $recall->affectedBatches()->attach($batch->id, [
                    'quarantine_status' => 'pending',
                    'quantity_affected' => $batch->quantity,
                ]);

                // Mark batch as recalled
                $batch->update(['recall_date' => now()]);
            }

            return $recall;
        });

        // Dispatch customer notifications after transaction commits
        NotifyRecallAffectedCustomers::dispatch($recall);

        return $recall;
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
     * Detect all affected batches for a recall scenario.
     */
    public function detectAffectedBatches(
        int $businessId,
        ?int $productId = null,
        ?string $batchLotNumber = null
    ): \Illuminate\Support\Collection {
        $query = BatchLot::forBusiness($businessId)
            ->whereNull('recall_date')
            ->activeBatches();

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($batchLotNumber) {
            $query->where(function ($q) use ($batchLotNumber) {
                $q->where('batch_number', $batchLotNumber)
                    ->orWhere('lot_number', $batchLotNumber);
            });
        }

        return $query->get();
    }

    /**
     * Quarantine an affected batch in a recall
     */
    public function quarantineAffectedBatch(
        RecallEvent $recall,
        BatchLot $batchLot,
        ?string $notes = null
    ): RecallEvent {
        $recall->affectedBatches()->updateExistingPivot($batchLot->id, [
            'quarantine_status' => 'quarantined',
            'quarantined_at' => now(),
            'notes' => $notes,
        ]);

        $batchLot->quarantine();

        // Log traceability event
        $this->logTraceability([
            'business_id' => $recall->business_id,
            'product_id' => $batchLot->product_id,
            'batch_lot_number' => $batchLot->batch_number ?? $batchLot->lot_number,
            'type' => 'recall',
            'quantity' => $batchLot->quantity,
            'user_id' => auth()->id() ?? $recall->user_id,
            'notes' => "Quarantined via recall #{$recall->id}: {$recall->reason}",
        ]);

        return $recall->fresh();
    }

    /**
     * Release an affected batch from quarantine
     */
    public function releaseAffectedBatch(
        RecallEvent $recall,
        BatchLot $batchLot
    ): RecallEvent {
        $recall->affectedBatches()->updateExistingPivot($batchLot->id, [
            'quarantine_status' => 'released',
            'resolved_at' => now(),
        ]);

        $batchLot->release();

        return $recall->fresh();
    }

    /**
     * Dispose of an affected batch
     */
    public function disposeAffectedBatch(
        RecallEvent $recall,
        BatchLot $batchLot,
        ?string $notes = null
    ): RecallEvent {
        $recall->affectedBatches()->updateExistingPivot($batchLot->id, [
            'quarantine_status' => 'disposed',
            'resolved_at' => now(),
            'notes' => $notes,
        ]);

        $batchLot->update([
            'status' => 'disposed',
            'quantity' => 0,
        ]);

        $this->logTraceability([
            'business_id' => $recall->business_id,
            'product_id' => $batchLot->product_id,
            'batch_lot_number' => $batchLot->batch_number ?? $batchLot->lot_number,
            'type' => 'recall',
            'quantity' => 0,
            'user_id' => auth()->id() ?? $recall->user_id,
            'notes' => "Disposed via recall #{$recall->id}" . ($notes ? ": {$notes}" : ''),
        ]);

        return $recall->fresh();
    }

    /**
     * Get recall summary with affected batch details
     */
    public function getRecallSummary(RecallEvent $recall): array
    {
        $recall->load(['affectedBatches.product', 'product']);

        $batches = $recall->affectedBatches;

        return [
            'recall' => [
                'id' => $recall->id,
                'reason' => $recall->reason,
                'status' => $recall->status,
                'initiated_at' => $recall->initiated_at->toIso8601String(),
                'resolved_at' => $recall->resolved_at?->toIso8601String(),
                'product_name' => $recall->product?->name,
            ],
            'affected_batches' => $batches->map(fn($b) => [
                'id' => $b->id,
                'batch_number' => $b->batch_number,
                'lot_number' => $b->lot_number,
                'product_name' => $b->product?->name,
                'quantity' => $b->quantity,
                'expiry_date' => $b->expiry_date?->toIso8601String(),
                'quarantine_status' => $b->pivot->quarantine_status,
                'quarantined_at' => $b->pivot->quarantined_at?->toIso8601String(),
                'quantity_affected' => $b->pivot->quantity_affected,
            ])->toArray(),
            'summary' => [
                'total_batches' => $batches->count(),
                'total_quantity_affected' => $batches->sum('pivot.quantity_affected'),
                'quarantined_count' => $batches->where('pivot.quarantine_status', 'quarantined')->count(),
                'released_count' => $batches->where('pivot.quarantine_status', 'released')->count(),
                'disposed_count' => $batches->where('pivot.quarantine_status', 'disposed')->count(),
                'pending_count' => $batches->where('pivot.quarantine_status', 'pending')->count(),
            ],
        ];
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
     * Get affected products for a recall (legacy method, delegates to getRecallSummary)
     */
    public function getAffectedProductsForRecall(int $businessId, RecallEvent $recall): array
    {
        $summary = $this->getRecallSummary($recall);

        return [
            'recall_id' => $recall->id,
            'total_affected_batches' => $summary['summary']['total_batches'],
            'batches' => $summary['affected_batches'],
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
