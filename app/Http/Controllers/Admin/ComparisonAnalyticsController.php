<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComparisonHistory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComparisonAnalyticsController extends Controller
{
    /**
     * Show comparison analytics dashboard.
     */
    public function index(Request $request)
    {
        $businessId = auth()->user()->business_id;

        // Overall stats
        $totalComparisons = ComparisonHistory::count();
        $totalViews = ComparisonHistory::sum('view_count');
        $uniqueProductsCompared = DB::table('comparison_history')
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(product_ids, "$[*]")) as all_ids')
            ->get()
            ->flatMap(fn($row) => explode(',', $row->all_ids))
            ->filter()
            ->unique()
            ->count();

        $topViewed = ComparisonHistory::orderByDesc('view_count')
            ->limit(10)
            ->get();

        // Most compared products
        $mostCompared = $this->getMostComparedProducts($businessId);

        // Comparisons over time (last 30 days)
        $dailyTrend = ComparisonHistory::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as comparisons, SUM(view_count) as views')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top shared comparisons
        $topShared = ComparisonHistory::where('view_count', '>', 1)
            ->orderByDesc('view_count')
            ->limit(10)
            ->get();

        // Average products per comparison
        $avgProductsPerComparison = ComparisonHistory::selectRaw('AVG(JSON_LENGTH(product_ids)) as avg_products')->value('avg_products') ?? 0;

        return view('admin.comparison-analytics.index', compact(
            'totalComparisons',
            'totalViews',
            'uniqueProductsCompared',
            'topViewed',
            'mostCompared',
            'dailyTrend',
            'topShared',
            'avgProductsPerComparison'
        ));
    }

    /**
     * Get the most compared products with their counts.
     */
    protected function getMostComparedProducts(?int $businessId = null)
    {
        // Extract all product IDs from comparisons and count occurrences
        $allComparisons = ComparisonHistory::select('product_ids')->get();
        $productCounts = [];

        foreach ($allComparisons as $comparison) {
            $ids = $comparison->product_ids ?? [];
            foreach ($ids as $id) {
                $productCounts[$id] = ($productCounts[$id] ?? 0) + 1;
            }
        }

        arsort($productCounts);
        $topIds = array_slice(array_keys($productCounts), 0, 15);

        if (empty($topIds)) {
            return collect([]);
        }

        $products = Product::whereIn('id', $topIds)
            ->with(['category'])
            ->get()
            ->keyBy('id');

        return collect($topIds)
            ->map(fn($id) => [
                'product' => $products[$id] ?? null,
                'count'   => $productCounts[$id] ?? 0,
            ])
            ->filter(fn($item) => $item['product'] !== null);
    }
}
