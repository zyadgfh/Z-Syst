<?php

namespace App\Services;

use App\Models\AutoOrderSuggestion;
use App\Models\Product;
use App\Models\SaleDetails;
use App\Models\SalesForecast;
use Carbon\CarbonImmutable;

/**
 * Demand forecasting: weighted moving average + weekday seasonality, stored
 * into sales_forecasts and wired into AutoOrder suggestions (reorder point =
 * daily demand × lead time + safety stock).
 */
class ForecastService
{
    public function generate(int $businessId, int $horizonDays = 14): int
    {
        $since = now()->subDays(90)->toDateString();

        $rows = SaleDetails::query()
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->where('sales.business_id', $businessId)
            ->where('sales.saleDate', '>=', $since)
            ->groupBy('sale_details.product_id')
            ->selectRaw('sale_details.product_id, SUM(sale_details.quantities) total_qty, COUNT(DISTINCT DATE(sales.saleDate)) active_days')
            ->get();

        $weekday = $this->weekdayFactors($businessId);
        $written = 0;

        foreach ($rows as $row) {
            $activeDays = max(1, (int) $row->active_days);
            $avgPerActiveDay = ((float) $row->total_qty) / $activeDays;
            $dailyDemand = $avgPerActiveDay * (min($activeDays, 90) / 7); // spread over week days

            foreach (range(1, $horizonDays) as $offset) {
                $date = CarbonImmutable::parse(now()->toDateString())->addDays($offset);
                $factor = $weekday[$date->dayOfWeek] ?? 1.0;
                $predicted = round(max(0, $dailyDemand * $factor), 2);

                SalesForecast::updateOrCreate([
                    'business_id' => $businessId,
                    'product_id' => $row->product_id,
                    'forecast_date' => $date->toDateString(),
                ], [
                    'predicted_quantity' => $predicted,
                    'predicted_revenue' => $predicted * (float) (Product::find($row->product_id)->sales_price ?? 0),
                    'confidence_score' => round(min(85, 40 + $activeDays / 2), 2),
                ]);
                $written++;
            }

            $this->syncAutoOrder($businessId, (int) $row->product_id, $dailyDemand);
        }

        return $written;
    }

    /** Feed reorder math into the auto-order suggestion engine. */
    protected function syncAutoOrder(int $businessId, int $productId, float $dailyDemand): void
    {
        $suggestion = AutoOrderSuggestion::firstOrNew([
            'business_id' => $businessId, 'product_id' => $productId,
        ]);

        $suggestion->enabled = $suggestion->enabled ?? true;
        $suggestion->lead_time_days = $suggestion->lead_time_days ?? 7;
        $lead = (int) $suggestion->lead_time_days;
        $safety = round($dailyDemand * 3, 2);
        $suggestion->reorder_point = round($dailyDemand * $lead + $safety, 2);
        $suggestion->safety_stock = $safety;
        $suggestion->max_stock_level = $suggestion->max_stock_level ?? round(max(10, $dailyDemand * 30), 2);
        $suggestion->min_stock_level = $suggestion->min_stock_level ?? round($dailyDemand * 7, 2);
        $suggestion->save();
    }

    protected function weekdayFactors(int $businessId): array
    {
        $rows = SaleDetails::query()
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->where('sales.business_id', $businessId)
            ->where('sales.saleDate', '>=', now()->subDays(56)->toDateString())
            ->groupBy('dow')
            ->selectRaw('EXTRACT(DOW FROM sales.saleDate) dow, SUM(sale_details.quantities) qty')
            ->pluck('qty', 'dow');

        $avg = max(1, (float) $rows->avg());
        $factors = [];
        foreach (range(0, 6) as $dow) {
            $factors[$dow] = isset($rows[$dow]) ? round($rows[$dow] / $avg, 2) : 0.7;
        }

        return $factors;
    }
}
