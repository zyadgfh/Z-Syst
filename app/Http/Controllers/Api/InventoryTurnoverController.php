<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTurnoverReport;
use App\Models\Product;
use App\Models\ProductInventoryAnalysis;
use App\Services\InventoryTurnoverService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryTurnoverController extends Controller
{
    protected InventoryTurnoverService $inventoryTurnoverService;

    public function __construct(InventoryTurnoverService $inventoryTurnoverService)
    {
        $this->inventoryTurnoverService = $inventoryTurnoverService;
    }

    /**
     * Get inventory turnover summary with key metrics.
     *
     * @return JsonResponse
     */
    public function summary()
    {
        $businessId = auth()->user()->business_id;

        $summary = $this->inventoryTurnoverService->getSummary($businessId);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $summary,
        ]);
    }

    /**
     * Generate or get inventory turnover report for a period.
     *
     * @return JsonResponse
     */
    public function report(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $request->validate([
            'report_type' => 'in:monthly,quarterly,yearly',
            'date' => 'nullable|date',
        ]);

        $reportType = $request->report_type ?? 'monthly';
        $customDate = $request->date ? Carbon::parse($request->date) : null;

        $result = $this->inventoryTurnoverService->generateReport($businessId, $reportType, $customDate);

        return response()->json([
            'message' => __('Report generated successfully.'),
            'data' => $result,
        ]);
    }

    /**
     * Get analysis for all products.
     *
     * @return JsonResponse
     */
    public function products(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $request->validate([
            'report_type' => 'in:monthly,quarterly,yearly',
            'movement' => 'nullable|in:fast,medium,slow,dead',
            'abc' => 'nullable|in:A,B,C',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|in:turnover_ratio,dio,stock_velocity,current_stock_value,movement_category,abc_category',
            'sort_dir' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ProductInventoryAnalysis::where('product_inventory_analysis.business_id', $businessId)
            ->join('products', 'products.id', '=', 'product_inventory_analysis.product_id')
            ->select(
                'product_inventory_analysis.*',
                'products.productName',
                'products.productCode',
                'products.sales_price',
                'products.purchase_with_tax'
            );

        // Filter by latest report of the specified type
        $reportType = $request->report_type ?? 'monthly';
        $latestReport = InventoryTurnoverReport::where('business_id', $businessId)
            ->where('report_type', $reportType)
            ->latest('period_end')
            ->first();

        if ($latestReport) {
            $query->where('report_id', $latestReport->id);
        }

        // Filter by movement category
        if ($request->movement) {
            $query->where('movement_category', $request->movement);
        }

        // Filter by ABC category
        if ($request->abc) {
            $query->where('abc_category', $request->abc);
        }

        // Search by product name or code
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('products.productName', 'like', "%{$search}%")
                    ->orWhere('products.productCode', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->sort_by ?? 'turnover_ratio';
        $sortDir = $request->sort_dir ?? 'asc';
        $allowedSorts = ['turnover_ratio', 'dio', 'stock_velocity', 'current_stock_value', 'movement_category', 'abc_category'];

        if (in_array($sortBy, $allowedSorts)) {
            $sortColumn = match ($sortBy) {
                'dio' => 'days_inventory_outstanding',
                default => $sortBy,
            };
            $query->orderBy($sortColumn, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = $request->per_page ?? 20;
        $products = $query->paginate($perPage);

        // Add movement label
        $products->getCollection()->transform(function ($item) {
            $item->movement_label = match ($item->movement_category) {
                'fast' => 'سريع الحركة',
                'medium' => 'متوسط الحركة',
                'slow' => 'بطيء الحركة',
                'dead' => 'راكد',
                default => 'غير محدد',
            };
            $item->abc_label = match ($item->abc_category) {
                'A' => 'عالي القيمة',
                'B' => 'متوسط القيمة',
                'C' => 'منخفض القيمة',
                default => 'غير مصنف',
            };

            return $item;
        });

        // Get statistics summary
        $stats = [
            'total_fast' => ProductInventoryAnalysis::where('business_id', $businessId)->where('movement_category', 'fast')->count(),
            'total_medium' => ProductInventoryAnalysis::where('business_id', $businessId)->where('movement_category', 'medium')->count(),
            'total_slow' => ProductInventoryAnalysis::where('business_id', $businessId)->where('movement_category', 'slow')->count(),
            'total_dead' => ProductInventoryAnalysis::where('business_id', $businessId)->where('movement_category', 'dead')->count(),
        ];

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $products,
            'stats' => $stats,
        ]);
    }

    /**
     * Get analysis for a specific product.
     *
     * @param  int  $productId
     * @return JsonResponse
     */
    public function product($productId, Request $request)
    {
        $businessId = auth()->user()->business_id;

        $product = Product::where('id', $productId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        $request->validate([
            'report_type' => 'in:monthly,quarterly,yearly',
        ]);
        $reportType = $request->report_type ?? 'monthly';

        // Get the latest report
        $latestReport = InventoryTurnoverReport::where('business_id', $businessId)
            ->where('report_type', $reportType)
            ->latest('period_end')
            ->first();

        if ($latestReport) {
            $analysis = ProductInventoryAnalysis::where('business_id', $businessId)
                ->where('product_id', $productId)
                ->where('report_id', $latestReport->id)
                ->first();

            if ($analysis) {
                $analysis->movement_label = match ($analysis->movement_category) {
                    'fast' => 'سريع الحركة',
                    'medium' => 'متوسط الحركة',
                    'slow' => 'بطيء الحركة',
                    'dead' => 'راكد',
                    default => 'غير محدد',
                };
                $analysis->abc_label = match ($analysis->abc_category) {
                    'A' => 'عالي القيمة',
                    'B' => 'متوسط القيمة',
                    'C' => 'منخفض القيمة',
                    default => 'غير مصنف',
                };

                return response()->json([
                    'message' => __('Data fetched successfully.'),
                    'data' => [
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->productName,
                            'code' => $product->productCode,
                        ],
                        'analysis' => $analysis,
                        'report_period' => [
                            'start' => $latestReport->period_start->format('Y-m-d'),
                            'end' => $latestReport->period_end->format('Y-m-d'),
                            'type' => $reportType,
                        ],
                    ],
                ]);
            }
        }

        // If no saved analysis, calculate on the fly
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        if ($reportType === 'quarterly') {
            $periodStart = now()->startOfQuarter();
            $periodEnd = now()->endOfQuarter();
        } elseif ($reportType === 'yearly') {
            $periodStart = now()->startOfYear();
            $periodEnd = now()->endOfYear();
        }

        $analysis = $this->inventoryTurnoverService->analyzeProduct($product, $businessId, $periodStart, $periodEnd);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->productName,
                    'code' => $product->productCode,
                ],
                'analysis' => $analysis,
                'report_period' => [
                    'start' => $periodStart->format('Y-m-d'),
                    'end' => $periodEnd->format('Y-m-d'),
                    'type' => $reportType,
                ],
            ],
        ]);
    }

    /**
     * Get slow-moving and dead stock products.
     *
     * @return JsonResponse
     */
    public function slowMoving(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $request->validate([
            'category' => 'nullable|in:slow,dead,all',
        ]);

        $category = $request->category ?? 'all';

        $result = $this->inventoryTurnoverService->getSlowMovingProducts($businessId, $category);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $result,
        ]);
    }

    /**
     * Get ABC analysis for products.
     *
     * @return JsonResponse
     */
    public function abcAnalysis(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $request->validate([
            'period' => 'in:monthly,quarterly,yearly',
        ]);

        $periodType = $request->period ?? 'monthly';
        [$periodStart, $periodEnd] = match ($periodType) {
            'quarterly' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'yearly' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $result = $this->inventoryTurnoverService->performABCAnalysis($businessId, $periodStart, $periodEnd);

        // Update ABC categories in the database
        foreach ($result['products'] as $productData) {
            ProductInventoryAnalysis::where('business_id', $businessId)
                ->where('product_id', $productData['product_id'])
                ->update(['abc_category' => $productData['abc_category']]);
        }

        return response()->json([
            'message' => __('ABC analysis completed successfully.'),
            'data' => $result,
        ]);
    }

    /**
     * Get historical trends for inventory turnover.
     *
     * @return JsonResponse
     */
    public function trends(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $request->validate([
            'report_type' => 'in:monthly,quarterly,yearly',
            'limit' => 'nullable|integer|min:1|max:24',
        ]);

        $reportType = $request->report_type ?? 'monthly';
        $limit = $request->limit ?? 6;

        $reports = InventoryTurnoverReport::where('business_id', $businessId)
            ->where('report_type', $reportType)
            ->orderBy('period_end', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($report) {
                $meta = is_string($report->meta) ? json_decode($report->meta, true) : $report->meta;

                return [
                    'id' => $report->id,
                    'period_label' => $meta['period_label'] ?? '',
                    'period_start' => $report->period_start->format('Y-m-d'),
                    'period_end' => $report->period_end->format('Y-m-d'),
                    'inventory_turnover_ratio' => (float) $report->inventory_turnover_ratio,
                    'days_inventory_outstanding' => (float) $report->days_inventory_outstanding,
                    'total_cogs' => (float) $report->total_cogs,
                    'total_inventory_value' => (float) $report->total_inventory_value,
                    'created_at' => $report->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->reverse()
            ->values();

        return response()->json([
            'message' => __('Trends fetched successfully.'),
            'data' => [
                'report_type' => $reportType,
                'trends' => $reports,
                'summary' => [
                    'average_turnover' => $reports->count() > 0
                        ? round($reports->avg('inventory_turnover_ratio'), 2)
                        : 0,
                    'average_dio' => $reports->count() > 0
                        ? round($reports->avg('days_inventory_outstanding'), 2)
                        : 0,
                    'trend_direction' => $this->calculateTrendDirection($reports),
                ],
            ],
        ]);
    }

    /**
     * Calculate the trend direction based on last few reports.
     */
    private function calculateTrendDirection($reports): string
    {
        if ($reports->count() < 3) {
            return 'insufficient_data';
        }

        $recent = $reports->slice(-3);
        $values = $recent->pluck('inventory_turnover_ratio')->toArray();

        // Check if consistently improving (increasing)
        if ($values[2] > $values[1] && $values[1] > $values[0]) {
            return 'improving';
        }

        // Check if consistently declining
        if ($values[2] < $values[1] && $values[1] < $values[0]) {
            return 'declining';
        }

        // Check if stable (within 5% range)
        $avg = array_sum($values) / count($values);
        $maxDiff = max($values) - min($values);
        if ($maxDiff / max($avg, 0.01) < 0.05) {
            return 'stable';
        }

        return 'fluctuating';
    }
}
