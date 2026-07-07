<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardKPIsResource;
use App\Http\Resources\InventorySummaryResource;
use App\Http\Resources\LowStockProductResource;
use App\Http\Resources\ExpiringProductResource;
use App\Http\Resources\SalesTrendResource;
use App\Http\Resources\TopSellingProductResource;
use App\Http\Resources\StockTurnoverResource;
use App\Services\AnalyticsService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $analyticsService;
    protected $reportExportService;

    public function __construct(AnalyticsService $analyticsService, ReportExportService $reportExportService)
    {
        $this->analyticsService = $analyticsService;
        $this->reportExportService = $reportExportService;
    }

    /**
     * Get dashboard KPIs
     */
    public function getKPIs(Request $request): JsonResponse
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->query('start_date')) : null;
        $endDate = $request->has('end_date') ? Carbon::parse($request->query('end_date')) : null;

        $kpis = $this->analyticsService->getDashboardKPIs($startDate, $endDate);

        return response()->json([
            'data' => new DashboardKPIsResource($kpis),
            'meta' => [
                'period' => [
                    'start' => $startDate?->format('Y-m-d') ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
                    'end' => $endDate?->format('Y-m-d') ?? Carbon::now()->format('Y-m-d'),
                ]
            ]
        ]);
    }

    /**
     * Get sales trends
     */
    public function getSalesTrends(Request $request): JsonResponse
    {
        $period = $request->query('period', 'daily');
        $startDate = $request->has('start_date') ? Carbon::parse($request->query('start_date')) : null;
        $endDate = $request->has('end_date') ? Carbon::parse($request->query('end_date')) : null;

        $trends = $this->analyticsService->getSalesTrends($period, $startDate, $endDate);

        return response()->json([
            'data' => SalesTrendResource::collection($trends),
            'meta' => [
                'period' => $period,
                'date_range' => [
                    'start' => $startDate?->format('Y-m-d') ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
                    'end' => $endDate?->format('Y-m-d') ?? Carbon::now()->format('Y-m-d'),
                ]
            ]
        ]);
    }

    /**
     * Get top selling products
     */
    public function getTopSellingProducts(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 10), 50);
        $startDate = $request->has('start_date') ? Carbon::parse($request->query('start_date')) : null;
        $endDate = $request->has('end_date') ? Carbon::parse($request->query('end_date')) : null;

        $products = $this->analyticsService->getTopSellingProducts($limit, $startDate, $endDate);

        return response()->json([
            'data' => TopSellingProductResource::collection($products),
            'meta' => [
                'limit' => $limit,
                'date_range' => [
                    'start' => $startDate?->format('Y-m-d') ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
                    'end' => $endDate?->format('Y-m-d') ?? Carbon::now()->format('Y-m-d'),
                ]
            ]
        ]);
    }

    /**
     * Get sales by category
     */
    public function getSalesByCategory(Request $request): JsonResponse
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->query('start_date')) : null;
        $endDate = $request->has('end_date') ? Carbon::parse($request->query('end_date')) : null;

        $categories = $this->analyticsService->getSalesByCategory($startDate, $endDate);

        return response()->json([
            'data' => $categories,
            'meta' => [
                'date_range' => [
                    'start' => $startDate?->format('Y-m-d') ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
                    'end' => $endDate?->format('Y-m-d') ?? Carbon::now()->format('Y-m-d'),
                ]
            ]
        ]);
    }

    /**
     * Get sales by branch
     */
    public function getSalesByBranch(Request $request): JsonResponse
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->query('start_date')) : null;
        $endDate = $request->has('end_date') ? Carbon::parse($request->query('end_date')) : null;

        $branches = $this->analyticsService->getSalesByBranch($startDate, $endDate);

        return response()->json([
            'data' => $branches,
            'meta' => [
                'date_range' => [
                    'start' => $startDate?->format('Y-m-d') ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
                    'end' => $endDate?->format('Y-m-d') ?? Carbon::now()->format('Y-m-d'),
                ]
            ]
        ]);
    }

    /**
     * Get payment method breakdown
     */
    public function getPaymentMethodBreakdown(Request $request): JsonResponse
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->query('start_date')) : null;
        $endDate = $request->has('end_date') ? Carbon::parse($request->query('end_date')) : null;

        $breakdown = $this->analyticsService->getPaymentMethodBreakdown($startDate, $endDate);

        return response()->json([
            'data' => $breakdown,
            'meta' => [
                'date_range' => [
                    'start' => $startDate?->format('Y-m-d') ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
                    'end' => $endDate?->format('Y-m-d') ?? Carbon::now()->format('Y-m-d'),
                ]
            ]
        ]);
    }

    /**
     * Get inventory summary
     */
    public function getInventorySummary(): JsonResponse
    {
        $summary = $this->analyticsService->getInventorySummary();

        return response()->json([
            'data' => new InventorySummaryResource($summary),
            'meta' => [
                'generated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 20), 50);

        $products = $this->analyticsService->getLowStockProducts($limit);

        return response()->json([
            'data' => LowStockProductResource::collection($products),
            'meta' => [
                'limit' => $limit,
                'total_count' => count($products)
            ]
        ]);
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStockProducts(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 20), 50);

        $products = $this->analyticsService->getOutOfStockProducts($limit);

        return response()->json([
            'data' => $products,
            'meta' => [
                'limit' => $limit,
                'total_count' => count($products)
            ]
        ]);
    }

    /**
     * Get expiring products
     */
    public function getExpiringProducts(Request $request): JsonResponse
    {
        $days = min($request->query('days', 30), 365);
        $limit = min($request->query('limit', 20), 50);

        $products = $this->analyticsService->getExpiringProducts($days, $limit);

        return response()->json([
            'data' => ExpiringProductResource::collection($products),
            'meta' => [
                'days' => $days,
                'limit' => $limit,
                'total_count' => count($products)
            ]
        ]);
    }

    /**
     * Get dead stock
     */
    public function getDeadStock(Request $request): JsonResponse
    {
        $days = min($request->query('days', 90), 365);
        $limit = min($request->query('limit', 20), 50);

        $products = $this->analyticsService->getDeadStock($days, $limit);

        return response()->json([
            'data' => $products,
            'meta' => [
                'days' => $days,
                'limit' => $limit,
                'total_count' => count($products)
            ]
        ]);
    }

    /**
     * Get fast moving products
     */
    public function getFastMovingProducts(Request $request): JsonResponse
    {
        $days = min($request->query('days', 30), 365);
        $limit = min($request->query('limit', 20), 50);

        $products = $this->analyticsService->getFastMovingProducts($days, $limit);

        return response()->json([
            'data' => $products,
            'meta' => [
                'days' => $days,
                'limit' => $limit,
                'total_count' => count($products)
            ]
        ]);
    }

    /**
     * Get stock turnover analysis
     */
    public function getStockTurnover(Request $request): JsonResponse
    {
        $days = min($request->query('days', 90), 365);

        $turnover = $this->analyticsService->getStockTurnover($days);

        return response()->json([
            'data' => StockTurnoverResource::collection($turnover),
            'meta' => [
                'days' => $days,
                'total_count' => count($turnover)
            ]
        ]);
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(): JsonResponse
    {
        $this->analyticsService->clearCache();

        return response()->json([
            'message' => 'Analytics cache cleared successfully.'
        ]);
    }

    /**
     * Export sales report
     */
    public function exportSalesReport(Request $request): JsonResponse
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->query('start_date')) : null;
        $endDate = $request->has('end_date') ? Carbon::parse($request->query('end_date')) : null;

        $export = $this->reportExportService->exportSalesReport($startDate, $endDate);

        return response()->json([
            'message' => 'Sales report exported successfully.',
            'data' => $export
        ]);
    }

    /**
     * Export inventory report
     */
    public function exportInventoryReport(): JsonResponse
    {
        $export = $this->reportExportService->exportInventoryReport();

        return response()->json([
            'message' => 'Inventory report exported successfully.',
            'data' => $export
        ]);
    }

    /**
     * Export expiring products report
     */
    public function exportExpiringProductsReport(Request $request): JsonResponse
    {
        $days = min($request->query('days', 30), 365);

        $export = $this->reportExportService->exportExpiringProductsReport($days);

        return response()->json([
            'message' => 'Expiring products report exported successfully.',
            'data' => $export
        ]);
    }

    /**
     * Export top selling products report
     */
    public function exportTopSellingProductsReport(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 20), 50);
        $startDate = $request->has('start_date') ? Carbon::parse($request->query('start_date')) : null;
        $endDate = $request->has('end_date') ? Carbon::parse($request->query('end_date')) : null;

        $export = $this->reportExportService->exportTopSellingProductsReport($limit, $startDate, $endDate);

        return response()->json([
            'message' => 'Top selling products report exported successfully.',
            'data' => $export
        ]);
    }

    /**
     * Export stock turnover report
     */
    public function exportStockTurnoverReport(Request $request): JsonResponse
    {
        $days = min($request->query('days', 90), 365);

        $export = $this->reportExportService->exportStockTurnoverReport($days);

        return response()->json([
            'message' => 'Stock turnover report exported successfully.',
            'data' => $export
        ]);
    }
}