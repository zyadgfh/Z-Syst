<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierRating;
use App\Models\SupplierContract;
use App\Models\SupplierPerformance;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    public function create(array $data): Supplier
    {
        return DB::transaction(function () use ($data) {
            return Supplier::create($data);
        });
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {
            $supplier->update($data);
            return $supplier;
        });
    }

    public function addRating(Supplier $supplier, array $data): SupplierRating
    {
        return SupplierRating::create([
            'supplier_id' => $supplier->id,
            'business_id' => $supplier->business_id,
            'rating' => $data['rating'],
            'category' => $data['category'],
            'review' => $data['review'] ?? null,
            'rated_by' => $data['rated_by'] ?? null,
            'rated_at' => now(),
        ]);
    }

    public function calculatePerformance(Supplier $supplier): SupplierPerformance
    {
        return DB::transaction(function () use ($supplier) {
            $performance = SupplierPerformance::create([
                'supplier_id' => $supplier->id,
                'business_id' => $supplier->business_id,
                'on_time_delivery_rate' => $this->calculateOnTimeDelivery($supplier),
                'quality_score' => $this->calculateQualityScore($supplier),
                'price_competitiveness' => $this->calculatePriceCompetitiveness($supplier),
                'responsiveness' => $this->calculateResponsiveness($supplier),
                'total_orders' => $this->getTotalOrders($supplier),
                'total_disputes' => $this->getTotalDisputes($supplier),
                'calculated_at' => now(),
            ]);

            $supplier->calculatePerformanceScore();

            return $performance;
        });
    }

    protected function calculateOnTimeDelivery(Supplier $supplier): float
    {
        $grns = \App\Models\GoodsReceivedNote::where('supplier_id', $supplier->id)->get();
        if ($grns->isEmpty()) return 100.0;

        $onTime = $grns->filter(function ($grn) {
            return $grn->received_date <= ($grn->purchaseOrder->expected_delivery_date ?? $grn->received_date);
        })->count();

        return ($onTime / $grns->count()) * 100;
    }

    protected function calculateQualityScore(Supplier $supplier): float
    {
        $qualityChecks = \App\Models\QualityCheck::whereHas('grnItem.grn', function ($query) use ($supplier) {
            $query->where('supplier_id', $supplier->id);
        })->get();

        if ($qualityChecks->isEmpty()) return 100.0;

        $passed = $qualityChecks->where('quality_status', 'passed')->count();
        return ($passed / $qualityChecks->count()) * 100;
    }

    protected function calculatePriceCompetitiveness(Supplier $supplier): float
    {
        // Compare with average market prices
        $poItems = \App\Models\PurchaseOrderItem::whereHas('purchaseOrder', function ($query) use ($supplier) {
            $query->where('supplier_id', $supplier->id);
        })->get();

        if ($poItems->isEmpty()) return 85.0;

        // Simple calculation - compare with product average purchase price
        $total = 0;
        foreach ($poItems as $item) {
            $avgPrice = \App\Models\Product::find($item->product_id)?->purchase_without_tax ?? $item->unit_price;
            $ratio = $avgPrice > 0 ? ($avgPrice / $item->unit_price) * 100 : 100;
            $total += min($ratio, 100);
        }

        return $total / $poItems->count();
    }

    protected function calculateResponsiveness(Supplier $supplier): float
    {
        // Calculate based on response time to POs
        $pos = \App\Models\PurchaseOrder::where('supplier_id', $supplier->id)->get();
        if ($pos->isEmpty()) return 85.0;

        $responded = $pos->whereNotNull('approved_at')->count();
        return ($responded / $pos->count()) * 100;
    }

    protected function getTotalOrders(Supplier $supplier): int
    {
        return $supplier->purchaseOrders()->count();
    }

    protected function getTotalDisputes(Supplier $supplier): int
    {
        return $supplier->purchaseOrders()->where('status', 'rejected')->count();
    }

    public function getByBusiness(int $businessId)
    {
        return Supplier::forBusiness($businessId)
            ->with(['ratings', 'contracts', 'performance'])
            ->latest()
            ->get();
    }

    public function getTopPerformers(int $businessId, int $limit = 10)
    {
        return Supplier::forBusiness($businessId)
            ->active()
            ->orderByDesc('performance_score')
            ->limit($limit)
            ->get();
    }
}
