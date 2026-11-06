<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierPerformance;
use App\Models\SupplierRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SupplierPerformanceController — supplier scorecards (C6).
 *
 * index: one row per supplier — stored performance metrics joined with the
 * live rating average and computed dispute ratio.
 * rate: record a rating, refresh the suppliers.rating snapshot and upsert
 * supplier_performance with the aggregated quality score.
 */
class SupplierPerformanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $businessId = auth()->user()->business_id;

        $rows = Supplier::where('suppliers.business_id', $businessId)
            ->leftJoin('supplier_performance', function ($join) {
                $join->on('supplier_performance.supplier_id', '=', 'suppliers.id')
                    ->whereColumn('supplier_performance.business_id', 'suppliers.business_id');
            })
            ->leftJoin('supplier_ratings', function ($join) {
                $join->on('supplier_ratings.supplier_id', '=', 'suppliers.id')
                    ->whereColumn('supplier_ratings.business_id', 'suppliers.business_id');
            })
            ->groupBy('suppliers.id', 'suppliers.company_name', 'suppliers.is_active',
                'supplier_performance.on_time_delivery_rate', 'supplier_performance.quality_score',
                'supplier_performance.price_competitiveness', 'supplier_performance.responsiveness',
                'supplier_performance.total_orders', 'supplier_performance.total_disputes')
            ->get([
                'suppliers.id',
                'suppliers.company_name',
                'suppliers.is_active',
                'supplier_performance.on_time_delivery_rate',
                'supplier_performance.quality_score',
                'supplier_performance.price_competitiveness',
                'supplier_performance.responsiveness',
                'supplier_performance.total_orders',
                'supplier_performance.total_disputes',
            ])
            ->map(function ($row) {
                $ratings = SupplierRating::where('supplier_id', $row->id)
                    ->where('business_id', auth()->user()->business_id);

                $row->ratings_count = (clone $ratings)->count();
                $row->avg_rating = round((float) (clone $ratings)->avg('rating'), 2);
                $row->score = $this->compositeScore($row);

                return $row;
            })
            ->sortByDesc('score')
            ->values();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $rows,
        ]);
    }

    public function rate(Request $request, Supplier $supplier): JsonResponse
    {
        abort_unless($supplier->business_id === auth()->user()->business_id, 404);

        $validated = $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'category' => 'nullable|string|max:50', // quality | delivery | price | service
            'review' => 'nullable|string|max:1000',
        ]);

        // Path B: rated_by is a legacy integer FK — uuid actors stay null.
        $actorId = auth()->id();
        $rating = SupplierRating::create($validated + [
            'supplier_id' => $supplier->id,
            'business_id' => $supplier->business_id,
            'rated_by' => is_int($actorId) ? $actorId : (ctype_digit((string) $actorId) ? (int) $actorId : null),
            'rated_at' => now(),
        ]);

        // Refresh the supplier snapshot rating.
        $avg = (float) SupplierRating::where('supplier_id', $supplier->id)
            ->where('business_id', $supplier->business_id)
            ->avg('rating');
        $supplier->update(['rating' => round($avg, 2)]);

        // Upsert the aggregate performance row (quality score from ratings;
        // delivery/dispute metrics stay service-maintained).
        $performance = SupplierPerformance::firstOrNew([
            'supplier_id' => $supplier->id,
            'business_id' => $supplier->business_id,
        ]);
        $performance->quality_score = round($avg * 20, 1); // 5-star scale → 100
        $performance->calculated_at = now();
        $performance->save();

        return response()->json([
            'message' => __('Rating recorded successfully.'),
            'data' => ['rating' => $rating, 'supplier_rating' => $supplier->rating],
        ], 201);
    }

    /**
     * Composite 0–100 score: 40% on-time delivery, 30% quality, 20% price,
     * 10% responsiveness — missing components redistribute their weight.
     */
    private function compositeScore($row): float
    {
        $parts = [
            [(float) ($row->on_time_delivery_rate ?? 0), 0.4],
            [(float) ($row->quality_score ?? 0), 0.3],
            [(float) ($row->price_competitiveness ?? 0), 0.2],
            [(float) ($row->responsiveness ?? 0), 0.1],
        ];

        $hasData = array_filter($parts, fn ($p) => $p[0] > 0);
        if (! $hasData) {
            return $row->avg_rating > 0 ? round($row->avg_rating / 5 * 100, 1) : 0.0;
        }

        $weightSum = array_sum(array_map(fn ($p) => $p[1], $hasData));
        $score = array_sum(array_map(fn ($p) => $p[0] * $p[1], $hasData)) / max($weightSum, 0.0001);

        return round($score, 1);
    }
}
