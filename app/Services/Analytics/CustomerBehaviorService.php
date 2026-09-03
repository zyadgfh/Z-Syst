<?php

namespace App\Services\Analytics;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CustomerBehaviorService
{
    /**
     * Get customer behavior analytics.
     *
     * @param int $customerId
     * @param int $businessId
     * @return array
     */
    public function getCustomerBehavior(int $customerId, int $businessId): array
    {
        $customer = Customer::findOrFail($customerId);
        
        return [
            'purchase_patterns' => $this->getPurchasePatterns($customer, $businessId),
            'product_preferences' => $this->getProductPreferences($customer, $businessId),
            'timing_patterns' => $this->getTimingPatterns($customer, $businessId),
            'spending_trends' => $this->getSpendingTrends($customer, $businessId),
        ];
    }

    /**
     * Get purchase patterns.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return array
     */
    protected function getPurchasePatterns(Customer $customer, int $businessId): array
    {
        $sales = Sale::where('business_id', $businessId)
            ->where('party_id', $customer->id)
            ->where('created_at', '>=', now()->subDays(180))
            ->get();

        return [
            'total_purchases' => $sales->count(),
            'average_purchase_value' => $sales->avg('totalAmount') ?? 0,
            'purchase_frequency' => $sales->count() / 6, // per month average
            'most_common_day' => $this->getMostCommonPurchaseDay($sales),
        ];
    }

    /**
     * Get product preferences.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return array
     */
    protected function getProductPreferences(Customer $customer, int $businessId): array
    {
        $sales = Sale::where('business_id', $businessId)
            ->where('party_id', $customer->id)
            ->with('details.product')
            ->get();

        $productCounts = collect();

        foreach ($sales as $sale) {
            foreach ($sale->details as $detail) {
                $productId = $detail->product_id;
                $productCounts[$productId] = ($productCounts[$productId] ?? 0) + $detail->quantities;
            }
        }

        $topProducts = $productCounts->sortDesc()->take(5);

        $preferences = [];
        foreach ($topProducts as $productId => $count) {
            $product = Product::find($productId);
            $preferences[] = [
                'product_id' => $productId,
                'product_name' => $product ? $product->productName : 'Unknown',
                'purchase_count' => $count,
            ];
        }

        return [
            'top_products' => $preferences,
            'total_unique_products' => $productCounts->count(),
        ];
    }

    /**
     * Get timing patterns.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return array
     */
    protected function getTimingPatterns(Customer $customer, int $businessId): array
    {
        $sales = Sale::where('business_id', $businessId)
            ->where('party_id', $customer->id)
            ->where('created_at', '>=', now()->subDays(180))
            ->get();

        $hourlyDistribution = array_fill(0, 24, 0);
        $dailyDistribution = array_fill(0, 7, 0);

        foreach ($sales as $sale) {
            $hour = $sale->created_at->hour;
            $day = $sale->created_at->dayOfWeek;
            
            $hourlyDistribution[$hour]++;
            $dailyDistribution[$day]++;
        }

        return [
            'peak_hour' => array_keys($hourlyDistribution, max($hourlyDistribution))[0] ?? 0,
            'peak_day' => array_keys($dailyDistribution, max($dailyDistribution))[0] ?? 0,
            'hourly_distribution' => $hourlyDistribution,
            'daily_distribution' => $dailyDistribution,
        ];
    }

    /**
     * Get spending trends.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return array
     */
    protected function getSpendingTrends(Customer $customer, int $businessId): array
    {
        $monthlySpending = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $total = Sale::where('business_id', $businessId)
                ->where('party_id', $customer->id)
                ->whereYear($date->year)
                ->whereMonth($date->month)
                ->sum('totalAmount');
            
            $monthlySpending[] = [
                'month' => $date->format('Y-m'),
                'amount' => $total,
            ];
        }

        return [
            'monthly_spending' => $monthlySpending,
            'trend' => $this->calculateTrend($monthlySpending),
        ];
    }

    /**
     * Get most common purchase day.
     *
     * @param Collection $sales
     * @return int
     */
    protected function getMostCommonPurchaseDay(Collection $sales): int
    {
        $dayCounts = array_fill(0, 7, 0);

        foreach ($sales as $sale) {
            $dayCounts[$sale->created_at->dayOfWeek]++;
        }

        return array_keys($dayCounts, max($dayCounts))[0] ?? 0;
    }

    /**
     * Calculate spending trend.
     *
     * @param array $monthlySpending
     * @return string
     */
    protected function calculateTrend(array $monthlySpending): string
    {
        if (count($monthlySpending) < 2) {
            return 'stable';
        }

        $first = $monthlySpending[0]['amount'];
        $last = $monthlySpending[count($monthlySpending) - 1]['amount'];

        if ($last > $first * 1.1) {
            return 'increasing';
        }

        if ($last < $first * 0.9) {
            return 'decreasing';
        }

        return 'stable';
    }

    /**
     * Segment customers based on behavior.
     *
     * @param int $businessId
     * @return array
     */
    public function segmentCustomers(int $businessId): array
    {
        $customers = Customer::where('business_id', $businessId)->get();

        $segments = [
            'high_value' => collect(),
            'frequent' => collect(),
            'at_risk' => collect(),
            'new' => collect(),
            'inactive' => collect(),
        ];

        foreach ($customers as $customer) {
            $behavior = $this->getCustomerBehavior($customer->id, $businessId);
            
            $totalSpent = Sale::where('business_id', $businessId)
                ->where('party_id', $customer->id)
                ->sum('totalAmount');

            $lastPurchase = Sale::where('business_id', $businessId)
                ->where('party_id', $customer->id)
                ->latest()
                ->first();

            $daysSinceLastPurchase = $lastPurchase 
                ? $lastPurchase->created_at->diffInDays(now())
                : 999;

            if ($totalSpent > 5000) {
                $segments['high_value']->push($customer);
            }

            if ($behavior['purchase_patterns']['purchase_frequency'] > 2) {
                $segments['frequent']->push($customer);
            }

            if ($daysSinceLastPurchase > 90) {
                $segments['inactive']->push($customer);
            }

            if ($daysSinceLastPurchase > 60 && $daysSinceLastPurchase <= 90) {
                $segments['at_risk']->push($customer);
            }

            if ($daysSinceLastPurchase <= 30 && $totalSpent < 500) {
                $segments['new']->push($customer);
            }
        }

        return [
            'high_value' => $segments['high_value']->count(),
            'frequent' => $segments['frequent']->count(),
            'at_risk' => $segments['at_risk']->count(),
            'new' => $segments['new']->count(),
            'inactive' => $segments['inactive']->count(),
        ];
    }
}