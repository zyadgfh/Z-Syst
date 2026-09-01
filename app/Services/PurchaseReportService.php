<?php

namespace App\Services;

use App\Models\AgingReport;
use App\Models\GoodsReceivedNote;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;

class PurchaseReportService
{
    public function getAnalyticsDashboard(int $businessId): array
    {
        return [
            'total_orders' => PurchaseOrder::forBusiness($businessId)->count(),
            'pending_orders' => PurchaseOrder::forBusiness($businessId)->pending()->count(),
            'total_grns' => GoodsReceivedNote::forBusiness($businessId)->count(),
            'pending_grns' => GoodsReceivedNote::forBusiness($businessId)->pending()->count(),
            'total_suppliers' => Supplier::forBusiness($businessId)->active()->count(),
            'total_spend' => PurchaseOrder::forBusiness($businessId)->sum('total_amount'),
        ];
    }

    public function getSupplierPerformance(int $businessId): array
    {
        return Supplier::forBusiness($businessId)
            ->with('performance')
            ->get()
            ->map(function ($supplier) {
                return [
                    'supplier_id' => $supplier->id,
                    'name' => $supplier->company_name,
                    'performance_score' => $supplier->performance_score,
                    'total_orders' => $supplier->performance->total_orders ?? 0,
                    'on_time_delivery' => $supplier->performance->on_time_delivery_rate ?? 0,
                    'quality_score' => $supplier->performance->quality_score ?? 0,
                ];
            })->toArray();
    }

    public function getPriceComparison(int $businessId, int $productId): array
    {
        $product = Product::find($productId);
        if (! $product) {
            return [];
        }

        $poItems = PurchaseOrderItem::whereHas('purchaseOrder', function ($query) use ($businessId) {
            $query->forBusiness($businessId);
        })->where('product_id', $productId)->get();

        return $poItems->map(function ($item) {
            return [
                'supplier_id' => $item->purchaseOrder->supplier_id,
                'supplier_name' => $item->purchaseOrder->supplier->company_name ?? 'N/A',
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'total' => $item->total,
                'po_number' => $item->purchaseOrder->po_number,
                'po_date' => $item->purchaseOrder->order_date,
            ];
        })->toArray();
    }

    public function getPurchaseTrends(int $businessId, string $period = 'monthly'): array
    {
        $query = PurchaseOrder::forBusiness($businessId);

        if ($period === 'monthly') {
            $query->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as period, COUNT(*) as total, SUM(total_amount) as amount')
                ->groupBy('period')
                ->orderBy('period');
        } elseif ($period === 'yearly') {
            $query->selectRaw('YEAR(order_date) as period, COUNT(*) as total, SUM(total_amount) as amount')
                ->groupBy('period')
                ->orderBy('period');
        }

        return $query->get()->toArray();
    }

    public function getCostVariance(int $businessId): array
    {
        $poItems = PurchaseOrderItem::whereHas('purchaseOrder', function ($query) use ($businessId) {
            $query->forBusiness($businessId);
        })->get();

        $variances = [];
        foreach ($poItems as $item) {
            $expectedPrice = $item->product->purchase_without_tax ?? $item->unit_price;
            $variance = $item->unit_price - $expectedPrice;
            $variancePercentage = $expectedPrice > 0 ? ($variance / $expectedPrice) * 100 : 0;

            $variances[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'po_number' => $item->purchaseOrder->po_number,
                'expected_price' => $expectedPrice,
                'actual_price' => $item->unit_price,
                'variance' => $variance,
                'variance_percentage' => $variancePercentage,
            ];
        }

        return $variances;
    }

    public function getAgingReport(int $businessId): array
    {
        return AgingReport::forBusiness($businessId)
            ->with('supplier')
            ->latest()
            ->get()
            ->map(function ($report) {
                return [
                    'supplier_id' => $report->supplier_id,
                    'supplier_name' => $report->supplier->company_name ?? 'N/A',
                    'report_date' => $report->report_date->format('Y-m-d'),
                    'period_30' => $report->period_30,
                    'period_60' => $report->period_60,
                    'period_90' => $report->period_90,
                    'period_90_plus' => $report->period_90_plus,
                    'total' => $report->total,
                ];
            })->toArray();
    }

    public function getForecastReport(int $businessId): array
    {
        $trends = $this->getPurchaseTrends($businessId, 'monthly');
        $lastMonths = array_slice($trends, -6);

        $forecast = [];
        foreach ($lastMonths as $month) {
            $forecast[] = [
                'period' => $month['period'],
                'actual' => $month['amount'],
                'forecast' => $month['amount'] * 1.05, // 5% growth assumption
            ];
        }

        return $forecast;
    }
}
