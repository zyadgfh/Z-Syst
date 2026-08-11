<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SubscriptionAnalyticsService
{
    /**
     * Get subscription analytics dashboard data.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getDashboard(int $businessId, array $filters = []): array
    {
        return [
            'overview' => $this->getOverviewMetrics($businessId),
            'revenue' => $this->getRevenueMetrics($businessId, $filters),
            'churn' => $this->getChurnMetrics($businessId, $filters),
            'growth' => $this->getGrowthMetrics($businessId, $filters),
            'subscriptions' => $this->getSubscriptionDistribution($businessId),
        ];
    }

    /**
     * Get overview metrics.
     *
     * @param int $businessId
     * @return array
     */
    protected function getOverviewMetrics(int $businessId): array
    {
        $activeSubscriptions = Subscription::where('business_id', $businessId)
            ->where('status', 'active')
            ->count();

        $totalRevenue = SubscriptionPayment::where('business_id', $businessId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $mrr = $this->calculateMRR($businessId);
        $arr = $this->calculateARR($businessId);

        return [
            'active_subscriptions' => $activeSubscriptions,
            'monthly_recurring_revenue' => $mrr,
            'annual_recurring_revenue' => $arr,
            'current_month_revenue' => $totalRevenue,
        ];
    }

    /**
     * Get revenue metrics.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    protected function getRevenueMetrics(int $businessId, array $filters): array
    {
        $query = SubscriptionPayment::where('business_id', $businessId);

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $payments = $query->get();

        return [
            'total_revenue' => $payments->sum('amount'),
            'payment_count' => $payments->count(),
            'average_payment' => $payments->count() > 0 ? $payments->sum('amount') / $payments->count() : 0,
            'by_plan' => $payments->groupBy('plan_id')->map(function ($group) {
                $plan = Plan::find($group->first()->plan_id);
                return [
                    'plan_name' => $plan ? $plan->name : 'Unknown',
                    'revenue' => $group->sum('amount'),
                    'count' => $group->count(),
                ];
            }),
        ];
    }

    /**
     * Get churn metrics.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    protected function getChurnMetrics(int $businessId, array $filters): array
    {
        $cancelledSubscriptions = Subscription::where('business_id', $businessId)
            ->where('status', 'cancelled')
            ->where('cancelled_at', '>=', now()->subDays(30))
            ->get();

        $totalSubscriptions = Subscription::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $churnRate = $totalSubscriptions > 0 ? ($cancelledSubscriptions->count() / $totalSubscriptions) * 100 : 0;

        return [
            'cancelled_last_30_days' => $cancelledSubscriptions->count(),
            'churn_rate' => round($churnRate, 2),
            'average_subscription_length' => $this->calculateAverageSubscriptionLength($cancelledSubscriptions),
            'churn_by_plan' => $cancelledSubscriptions->groupBy('plan_id')->map->count(),
        ];
    }

    /**
     * Get growth metrics.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    protected function getGrowthMetrics(int $businessId, array $filters): array
    {
        $newSubscriptionsThisMonth = Subscription::where('business_id', $businessId)
            ->where('status', 'active')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $newSubscriptionsLastMonth = Subscription::where('business_id', $businessId)
            ->where('status', 'active')
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])
            ->count();

        $growthRate = $newSubscriptionsLastMonth > 0 
            ? (($newSubscriptionsThisMonth - $newSubscriptionsLastMonth) / $newSubscriptionsLastMonth) * 100 
            : 0;

        return [
            'new_this_month' => $newSubscriptionsThisMonth,
            'new_last_month' => $newSubscriptionsLastMonth,
            'growth_rate' => round($growthRate, 2),
            'monthly_new_subscriptions' => $this->getMonthlyNewSubscriptions($businessId, 6),
        ];
    }

    /**
     * Get subscription distribution by plan.
     *
     * @param int $businessId
     * @return array
     */
    protected function getSubscriptionDistribution(int $businessId): array
    {
        $subscriptions = Subscription::where('business_id', $businessId)
            ->where('status', 'active')
            ->with('plan')
            ->get();

        return [
            'total' => $subscriptions->count(),
            'by_plan' => $subscriptions->groupBy('plan_id')->map(function ($group) {
                $plan = $group->first()->plan;
                return [
                    'plan_name' => $plan ? $plan->name : 'Unknown',
                    'count' => $group->count(),
                    'percentage' => 0, // Will be calculated
                ];
            }),
        ];
    }

    /**
     * Calculate Monthly Recurring Revenue (MRR).
     *
     * @param int $businessId
     * @return float
     */
    protected function calculateMRR(int $businessId): float
    {
        return Subscription::where('business_id', $businessId)
            ->where('status', 'active')
            ->with('plan')
            ->get()
            ->sum(function ($subscription) {
                return $subscription->plan->billing_cycle === 'monthly' 
                    ? $subscription->amount 
                    : ($subscription->plan->billing_cycle === 'yearly' 
                        ? $subscription->amount / 12 
                        : $subscription->amount);
            });
    }

    /**
     * Calculate Annual Recurring Revenue (ARR).
     *
     * @param int $businessId
     * @return float
     */
    protected function calculateARR(int $businessId): float
    {
        return $this->calculateMRR($businessId) * 12;
    }

    /**
     * Calculate average subscription length.
     *
     * @param Collection $cancelledSubscriptions
     * @return float
     */
    protected function calculateAverageSubscriptionLength(Collection $cancelledSubscriptions): float
    {
        if ($cancelledSubscriptions->isEmpty()) {
            return 0;
        }

        $totalDays = $cancelledSubscriptions->sum(function ($sub) {
            return $sub->cancelled_at->diffInDays($sub->created_at);
        });

        return $totalDays / $cancelledSubscriptions->count();
    }

    /**
     * Get monthly new subscriptions.
     *
     * @param int $businessId
     * @param int $months
     * @return array
     */
    protected function getMonthlyNewSubscriptions(int $businessId, int $months): array
    {
        $monthlyData = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Subscription::where('business_id', $businessId)
                ->where('status', 'active')
                ->whereYear($date->year)
                ->whereMonth($date->month)
                ->count();

            $monthlyData[] = [
                'month' => $date->format('Y-m'),
                'count' => $count,
            ];
        }

        return $monthlyData;
    }
}