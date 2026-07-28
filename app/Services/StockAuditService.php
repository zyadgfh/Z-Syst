<?php

namespace App\Services;

use App\Models\StockAudit;
use App\Models\StockAuditDetail;
use App\Models\StockReconciliation;
use App\Models\StockMovement;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockAuditService
{
    /**
     * Create a new stock audit.
     *
     * @param array $data
     * @return StockAudit
     */
    public function createAudit(array $data): StockAudit
    {
        $data['audit_number'] = $this->generateAuditNumber($data['business_id']);
        $data['status'] = 'pending';
        
        return StockAudit::create($data);
    }

    /**
     * Generate a unique audit number.
     *
     * @param int $businessId
     * @return string
     */
    private function generateAuditNumber(int $businessId): string
    {
        $prefix = 'AUD-' . date('Ymd') . '-';
        $lastAudit = StockAudit::where('business_id', $businessId)
            ->where('audit_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastAudit ? (int)substr($lastAudit->audit_number, -4) + 1 : 1;
        
        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Start a stock audit (change status to in_progress).
     *
     * @param StockAudit $audit
     * @param int $userId
     * @return StockAudit
     */
    public function startAudit(StockAudit $audit, int $userId): StockAudit
    {
        $audit->update([
            'status' => 'in_progress',
            'audit_date' => now(),
            'user_id' => $userId,
        ]);

        return $audit->fresh();
    }

    /**
     * Complete a stock audit.
     *
     * @param StockAudit $audit
     * @return StockAudit
     */
    public function completeAudit(StockAudit $audit): StockAudit
    {
        $audit->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $audit->fresh();
    }

    /**
     * Cancel a stock audit.
     *
     * @param StockAudit $audit
     * @param string|null $reason
     * @return StockAudit
     */
    public function cancelAudit(StockAudit $audit, ?string $reason = null): StockAudit
    {
        $audit->update([
            'status' => 'cancelled',
            'notes' => $reason ? $audit->notes . ' - Cancelled: ' . $reason : $audit->notes . ' - Cancelled',
        ]);

        return $audit->fresh();
    }

    /**
     * Add audit details for a product.
     *
     * @param StockAudit $audit
     * @param array $detailData
     * @return StockAuditDetail
     */
    public function addAuditDetail(StockAudit $audit, array $detailData): StockAuditDetail
    {
        $detailData['stock_audit_id'] = $audit->id;
        $detailData['business_id'] = $audit->business_id;
        
        // Get system quantity from stock
        $stock = Stock::where('business_id', $audit->business_id)
            ->where('product_id', $detailData['product_id'])
            ->when(isset($detailData['stock_id']), function ($q) use ($detailData) {
                return $q->where('id', $detailData['stock_id']);
            })
            ->first();

        if ($stock) {
            $detailData['system_quantity'] = $stock->productStock;
            $detailData['batch_no'] = $stock->batch_no;
            $detailData['expire_date'] = $stock->expire_date;
            $detailData['unit_cost'] = $stock->product->purchase_with_tax ?? 0;
        }

        $detail = StockAuditDetail::create($detailData);
        $detail->calculateVariance();
        $detail->save();

        return $detail;
    }

    /**
     * Add multiple audit details in bulk.
     *
     * @param StockAudit $audit
     * @param array $details
     * @return array
     */
    public function addBulkAuditDetails(StockAudit $audit, array $details): array
    {
        $createdDetails = [];

        DB::transaction(function () use ($audit, $details, &$createdDetails) {
            foreach ($details as $detailData) {
                $createdDetails[] = $this->addAuditDetail($audit, $detailData);
            }
        });

        return $createdDetails;
    }

    /**
     * Auto-populate audit with all current stock.
     *
     * @param StockAudit $audit
     * @return array
     */
    public function autoPopulateAudit(StockAudit $audit): array
    {
        $stocks = Stock::where('business_id', $audit->business_id)
            ->where('productStock', '>', 0)
            ->with('product')
            ->get();

        $details = [];

        DB::transaction(function () use ($audit, $stocks, &$details) {
            foreach ($stocks as $stock) {
                $detail = $this->addAuditDetail($audit, [
                    'product_id' => $stock->product_id,
                    'stock_id' => $stock->id,
                    'physical_quantity' => $stock->productStock, // Initially same as system
                    'notes' => 'Auto-populated from current stock',
                ]);
                $details[] = $detail;
            }
        });

        return $details;
    }

    /**
     * Create stock reconciliation from audit detail.
     *
     * @param StockAuditDetail $detail
     * @param int $userId
     * @param string|null $reason
     * @return StockReconciliation
     */
    public function createReconciliation(StockAuditDetail $detail, int $userId, ?string $reason = null): StockReconciliation
    {
        if ($detail->variance == 0) {
            throw new \Exception('No variance to reconcile for this product.');
        }

        $adjustmentType = $detail->variance > 0 ? 'increase' : 'decrease';
        $adjustmentQuantity = abs($detail->variance);

        $reconciliation = StockReconciliation::create([
            'business_id' => $detail->business_id,
            'stock_audit_id' => $detail->stock_audit_id,
            'user_id' => $userId,
            'product_id' => $detail->product_id,
            'stock_id' => $detail->stock_id,
            'batch_no' => $detail->batch_no,
            'expire_date' => $detail->expire_date,
            'adjustment_type' => $adjustmentType,
            'previous_quantity' => $detail->system_quantity,
            'new_quantity' => $detail->physical_quantity,
            'adjustment_quantity' => $adjustmentQuantity,
            'unit_cost' => $detail->unit_cost,
            'adjustment_value' => $detail->variance_value,
            'reference_type' => 'stock_audit',
            'reference_id' => $detail->stock_audit_id,
            'reason' => $reason ?? 'Stock reconciliation from audit #' . $detail->stock_audit_id,
            'is_posted' => false,
        ]);

        return $reconciliation;
    }

    /**
     * Post reconciliation to actual stock.
     *
     * @param StockReconciliation $reconciliation
     * @return StockReconciliation
     */
    public function postReconciliation(StockReconciliation $reconciliation): StockReconciliation
    {
        if ($reconciliation->is_posted) {
            throw new \Exception('Reconciliation already posted.');
        }

        DB::transaction(function () use ($reconciliation) {
            $stock = Stock::find($reconciliation->stock_id);

            if (!$stock) {
                throw new \Exception('Stock record not found.');
            }

            $beforeQuantity = $stock->productStock;

            if ($reconciliation->adjustment_type == 'increase') {
                $stock->increment('productStock', $reconciliation->adjustment_quantity);
            } else {
                if ($stock->productStock < $reconciliation->adjustment_quantity) {
                    throw new \Exception('Insufficient stock for decrease adjustment.');
                }
                $stock->decrement('productStock', $reconciliation->adjustment_quantity);
            }

            $stock->refresh();

            // Log the stock movement
            StockMovement::create([
                'business_id' => $reconciliation->business_id,
                'product_id' => $reconciliation->product_id,
                'stock_id' => $reconciliation->stock_id,
                'user_id' => $reconciliation->user_id,
                'movement_type' => 'adjustment',
                'quantity' => $reconciliation->adjustment_quantity,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $stock->productStock,
                'batch_no' => $reconciliation->batch_no,
                'expire_date' => $reconciliation->expire_date,
                'reference_type' => 'stock_reconciliation',
                'reference_id' => $reconciliation->id,
                'notes' => $reconciliation->reason,
            ]);

            $reconciliation->update([
                'is_posted' => true,
                'posted_at' => now(),
            ]);
        });

        return $reconciliation->fresh();
    }

    /**
     * Post all reconciliations for an audit.
     *
     * @param StockAudit $audit
     * @return array
     */
    public function postAllReconciliations(StockAudit $audit): array
    {
        $pendingReconciliations = $audit->reconciliations()->where('is_posted', false)->get();
        $postedReconciliations = [];

        DB::transaction(function () use ($pendingReconciliations, &$postedReconciliations) {
            foreach ($pendingReconciliations as $reconciliation) {
                $postedReconciliations[] = $this->postReconciliation($reconciliation);
            }
        });

        return $postedReconciliations;
    }

    /**
     * Get audit summary statistics.
     *
     * @param StockAudit $audit
     * @return array
     */
    public function getAuditSummary(StockAudit $audit): array
    {
        $details = $audit->details;

        return [
            'total_items' => $details->count(),
            'total_system_quantity' => $details->sum('system_quantity'),
            'total_physical_quantity' => $details->sum('physical_quantity'),
            'total_variance' => $details->sum('variance'),
            'total_variance_value' => $details->sum('variance_value'),
            'positive_variances' => $details->where('variance_type', 'positive')->count(),
            'negative_variances' => $details->where('variance_type', 'negative')->count(),
            'no_variances' => $details->where('variance_type', 'none')->count(),
            'reconciliations_created' => $audit->reconciliations()->count(),
            'reconciliations_posted' => $audit->reconciliations()->where('is_posted', true)->count(),
        ];
    }

    /**
     * Get variance report for an audit.
     *
     * @param StockAudit $audit
     * @return array
     */
    public function getVarianceReport(StockAudit $audit): array
    {
        $details = $audit->details()->with('product')->get();

        return [
            'audit' => $audit->only(['id', 'audit_number', 'audit_type', 'status', 'audit_date']),
            'variances' => $details->map(function ($detail) {
                return [
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product->productName ?? 'Unknown',
                    'batch_no' => $detail->batch_no,
                    'system_quantity' => $detail->system_quantity,
                    'physical_quantity' => $detail->physical_quantity,
                    'variance' => $detail->variance,
                    'variance_type' => $detail->variance_type,
                    'variance_value' => $detail->variance_value,
                ];
            })->filter(function ($item) {
                return $item['variance'] != 0;
            })->values(),
        ];
    }
}