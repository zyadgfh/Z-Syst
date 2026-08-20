<?php

namespace App\Modules\Reports\Services;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Response;

class ReportService
{
    public function getSalesReport(array $data, int $businessId): array
    {
        $query = Sale::where('business_id', $businessId);

        if (!empty($data['start_date'])) {
            $query->where('saleDate', '>=', $data['start_date']);
        }
        if (!empty($data['end_date'])) {
            $query->where('saleDate', '<=', $data['end_date']);
        }

        $sales = $query->get();

        return [
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total'),
            'total_profit' => $sales->sum('lossProfit'),
            'paid_amount' => $sales->where('isPaid', 1)->sum('total'),
            'unpaid_amount' => $sales->where('isPaid', 0)->sum('dueAmount'),
        ];
    }

    public function generateSalesReportPdf(array $data, int $businessId): Response
    {
        $report = $this->getSalesReport($data, $businessId);
        // PDF generation would go here using DomPDF
        return response()->json(['format' => 'pdf', 'data' => $report]);
    }

    public function generateSalesReportExcel(array $data, int $businessId): Response
    {
        $report = $this->getSalesReport($data, $businessId);
        return response()->json(['format' => 'excel', 'data' => $report]);
    }

    public function getPurchaseReport(array $data, int $businessId): array
    {
        $query = Purchase::where('business_id', $businessId);

        if (!empty($data['start_date'])) {
            $query->where('purchaseDate', '>=', $data['start_date']);
        }
        if (!empty($data['end_date'])) {
            $query->where('purchaseDate', '<=', $data['end_date']);
        }

        $purchases = $query->get();

        return [
            'total_purchases' => $purchases->count(),
            'total_amount' => $purchases->sum('total'),
            'paid_amount' => $purchases->where('isPaid', 1)->sum('total'),
            'unpaid_amount' => $purchases->where('isPaid', 0)->sum('dueAmount'),
        ];
    }

    public function generatePurchaseReportPdf(array $data, int $businessId): Response
    {
        $report = $this->getPurchaseReport($data, $businessId);
        return response()->json(['format' => 'pdf', 'data' => $report]);
    }

    public function generatePurchaseReportExcel(array $data, int $businessId): Response
    {
        $report = $this->getPurchaseReport($data, $businessId);
        return response()->json(['format' => 'excel', 'data' => $report]);
    }

    public function getLossProfitReport(array $data, int $businessId): array
    {
        $query = Sale::where('business_id', $businessId);

        if (!empty($data['start_date'])) {
            $query->where('saleDate', '>=', $data['start_date']);
        }
        if (!empty($data['end_date'])) {
            $query->where('saleDate', '<=', $data['end_date']);
        }

        $sales = $query->get();

        return [
            'total_revenue' => $sales->sum('total'),
            'total_cost' => $sales->sum('subTotal'),
            'total_profit' => $sales->sum('lossProfit'),
            'profit_margin' => $sales->sum('total') > 0
                ? round(($sales->sum('lossProfit') / $sales->sum('total')) * 100, 2)
                : 0,
        ];
    }

    public function generateLossProfitReportPdf(array $data, int $businessId): Response
    {
        $report = $this->getLossProfitReport($data, $businessId);
        return response()->json(['format' => 'pdf', 'data' => $report]);
    }

    public function generateLossProfitReportExcel(array $data, int $businessId): Response
    {
        $report = $this->getLossProfitReport($data, $businessId);
        return response()->json(['format' => 'excel', 'data' => $report]);
    }

    public function getStockAuditReport(array $data, int $businessId): array
    {
        $query = Stock::where('business_id', $businessId);

        if (!empty($data['product_id'])) {
            $query->where('product_id', $data['product_id']);
        }

        $stocks = $query->get();

        return [
            'total_products' => $products = Product::where('business_id', $businessId)->count(),
            'total_stock_value' => $stocks->sum(fn ($s) => $s->productStock * $s->purchasePrice),
            'low_stock_count' => $stocks->where('productStock', '<=', fn ($s) => $s->alertQty ?? 10)->count(),
            'expired_count' => $stocks->where('expire_date', '<', now())->count(),
            'stocks' => $stocks,
        ];
    }

    public function generateStockAuditReportPdf(array $data, int $businessId): Response
    {
        $report = $this->getStockAuditReport($data, $businessId);
        return response()->json(['format' => 'pdf', 'data' => $report]);
    }

    public function generateStockAuditReportExcel(array $data, int $businessId): Response
    {
        $report = $this->getStockAuditReport($data, $businessId);
        return response()->json(['format' => 'excel', 'data' => $report]);
    }

    public function getFinancialAuditReport(array $data, int $businessId): array
    {
        $salesQuery = Sale::where('business_id', $businessId);
        $purchaseQuery = Purchase::where('business_id', $businessId);

        if (!empty($data['start_date'])) {
            $salesQuery->where('saleDate', '>=', $data['start_date']);
            $purchaseQuery->where('purchaseDate', '>=', $data['start_date']);
        }
        if (!empty($data['end_date'])) {
            $salesQuery->where('saleDate', '<=', $data['end_date']);
            $purchaseQuery->where('purchaseDate', '<=', $data['end_date']);
        }

        $sales = $salesQuery->get();
        $purchases = $purchaseQuery->get();

        $totalRevenue = $sales->sum('total');
        $totalCost = $purchases->sum('total');
        $totalPaidSales = $sales->where('isPaid', 1)->sum('total');
        $totalUnpaidSales = $sales->where('isPaid', 0)->sum('dueAmount');
        $totalPaidPurchases = $purchases->where('isPaid', 1)->sum('total');
        $totalUnpaidPurchases = $purchases->where('isPaid', 0)->sum('dueAmount');

        return [
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'net_profit' => $totalRevenue - $totalCost,
            'total_paid_sales' => $totalPaidSales,
            'total_unpaid_sales' => $totalUnpaidSales,
            'total_paid_purchases' => $totalPaidPurchases,
            'total_unpaid_purchases' => $totalUnpaidPurchases,
            'accounts_receivable' => $totalUnpaidSales,
            'accounts_payable' => $totalUnpaidPurchases,
        ];
    }

    public function generateFinancialAuditReportPdf(array $data, int $businessId): Response
    {
        $report = $this->getFinancialAuditReport($data, $businessId);
        return response()->json(['format' => 'pdf', 'data' => $report]);
    }

    public function generateFinancialAuditReportExcel(array $data, int $businessId): Response
    {
        $report = $this->getFinancialAuditReport($data, $businessId);
        return response()->json(['format' => 'excel', 'data' => $report]);
    }
}
