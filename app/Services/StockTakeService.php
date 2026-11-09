<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAudit;
use App\Models\StockAuditDetail;
use App\Models\StockMovement;

/**
 * Scan-based stock take: open a session seeded with every stocked line,
 * devices POST scans (code → counted++), and closing applies the variance
 * to stock (with a StockMovement trail).
 */
class StockTakeService
{
    public function open(int $businessId, $user, string $type = 'periodic'): StockAudit
    {
        $audit = StockAudit::create([
            'business_id' => $businessId,
            'user_id' => $user->id,
            'audit_number' => 'AUD-'.now()->format('Ymd').'-'.strtoupper(substr((string) \Illuminate\Support\Str::ulid(), -6)),
            'audit_type' => $type,
            'status' => 'in_progress',
            'audit_date' => now(),
        ]);

        foreach (Stock::forBusiness($businessId)->where('product_stock', '>', 0)->get() as $stock) {
            StockAuditDetail::create([
                'stock_audit_id' => $audit->id,
                'business_id' => $businessId,
                'product_id' => $stock->product_id,
                'stock_id' => $stock->id,
                'batch_no' => $stock->batch_no,
                'expire_date' => $stock->expire_date,
                'system_quantity' => (int) $stock->product_stock,
                'physical_quantity' => 0,
                'variance' => -(int) $stock->product_stock,
                'variance_type' => 'negative',
            ]);
        }

        return $audit;
    }

    /** Register a scanned code (product code/barcode) into the session. */
    /** Per-audit+product instance cache so repeated scans keep the same
     *  detail instance in sync with the cumulative count. */
    protected array $scanCache = [];

    public function scan(StockAudit $audit, string $code, int $qty = 1): ?StockAuditDetail
    {
        $businessId = (int) $audit->business_id;
        $productId = $this->resolveProductId($businessId, $code);
        if (!$productId) {
            return null;
        }

        $cacheKey = $audit->id.':'.$productId;
        $detail = $this->scanCache[$cacheKey]
            ?? StockAuditDetail::where('stock_audit_id', $audit->id)
                ->where('product_id', $productId)
                ->orderBy('id')->first();

        if (!$detail) {
            // Unlisted item found on shelf — add as a line.
            $stock = Stock::forBusiness($businessId)->where('product_id', $productId)->orderBy('id')->first();
            $detail = StockAuditDetail::create([
                'stock_audit_id' => $audit->id, 'business_id' => $businessId,
                'product_id' => $productId, 'stock_id' => $stock?->id,
                'batch_no' => $stock?->batch_no, 'expire_date' => $stock?->expire_date,
                'system_quantity' => (int) ($stock?->product_stock ?? 0),
                'physical_quantity' => 0, 'variance' => 0, 'variance_type' => 'none',
            ]);
        }

        $detail->physical_quantity = (int) $detail->physical_quantity + max(1, $qty);
        $detail->save();

        $this->recomputeLine($detail);
        $this->scanCache[$cacheKey] = $detail;

        return $detail;
    }

    public function recomputeLine(StockAuditDetail $detail): void
    {
        $variance = (int) $detail->physical_quantity - (int) $detail->system_quantity;
        $detail->update([
            'variance' => $variance,
            'variance_type' => $variance === 0 ? 'none' : ($variance > 0 ? 'positive' : 'negative'),
        ]);
    }

    /** Apply counted quantities to stock + movement trail. */
    public function apply(StockAudit $audit): array
    {
        if ($audit->status === 'completed') {
            return ['applied' => 0];
        }

        $applied = 0;
        foreach ($audit->details as $detail) {
            if ($detail->variance == 0 || !$detail->stock_id) {
                continue;
            }
            $stock = Stock::find($detail->stock_id);
            if (!$stock) {
                continue;
            }

            $before = (int) $stock->product_stock;
            $stock->update(['product_stock' => (int) $detail->physical_quantity]);

            StockMovement::create([
                'business_id' => $audit->business_id,
                'product_id' => $detail->product_id,
                'stock_id' => $stock->id,
                'movement_type' => 'adjustment',
                'quantity' => $detail->variance,
                'before_quantity' => $before,
                'after_quantity' => (int) $detail->physical_quantity,
                'batch_no' => $stock->batch_no,
                'reference_type' => 'stock_audit',
                'reference_id' => $audit->id,
                'user_id' => auth()->id(),
            ]);
            $applied++;
        }

        $audit->update(['status' => 'completed', 'completed_at' => now()]);

        return ['applied' => $applied, 'variance_lines' => $audit->details()->where('variance', '!=', 0)->count()];
    }

    protected function resolveProductId(int $businessId, string $code): ?int
    {
        $product = Product::where('business_id', $businessId)
            ->where(fn ($q) => $q->where('product_code', $code)->orWhere('barcode', $code))->first();
        if ($product) {
            return $product->id;
        }

        return \App\Models\Barcode::forBusiness($businessId)->where('barcode_number', $code)->value('product_id');
    }
}
