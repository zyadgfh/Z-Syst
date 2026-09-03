<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ApiUsage;
use App\Models\ApiQuota;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;

class ApiQuotaService
{
    use WithTransactionalOperations;

    /**
     * Track API usage.
     *
     * @param int $businessId
     * @param string $endpoint
     * @param string $method
     * @param int $responseCode
     * @return void
     */
    public function trackUsage(int $businessId, string $endpoint, string $method, int $responseCode): void
    {
        ApiUsage::create([
            'business_id' => $businessId,
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $responseCode,
            'created_at' => now(),
        ]);
    }

    /**
     * Check if business has exceeded quota.
     *
     * @param int $businessId
     * @return array
     */
    public function checkQuota(int $businessId): array
    {
        $quota = ApiQuota::where('business_id', $businessId)->first();

        if (!$quota) {
            return ['has_quota' => false, 'exceeded' => false];
        }

        $currentUsage = $this->getCurrentUsage($businessId, $quota->period);

        return [
            'has_quota' => true,
            'limit' => $quota->limit,
            'current' => $currentUsage,
            'remaining' => max(0, $quota->limit - $currentUsage),
            'exceeded' => $currentUsage >= $quota->limit,
            'percentage' => $quota->limit > 0 ? ($currentUsage / $quota->limit) * 100 : 0,
        ];
    }

    /**
     * Get current usage for a period.
     *
     * @param int $businessId
     * @param string $period
     * @return int
     */
    protected function getCurrentUsage(int $businessId, string $period): int
    {
        $query = ApiUsage::where('business_id', $businessId);

        switch ($period) {
            case 'hourly':
                $query->where('created_at', '>=', now()->subHour());
                break;
            case 'daily':
                $query->where('created_at', '>=', now()->startOfDay());
                break;
            case 'weekly':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'monthly':
                $query->where('created_at', '>=', now()->startOfMonth());
                break;
        }

        return $query->count();
    }

    /**
     * Set API quota for a business.
     *
     * @param int $businessId
     * @param int $limit
     * @param string $period
     * @return ApiQuota
     */
    public function setQuota(int $businessId, int $limit, string $period = 'monthly'): ApiQuota
    {
        return ApiQuota::updateOrCreate(
            ['business_id' => $businessId],
            [
                'limit' => $limit,
                'period' => $period,
            ]
        );
    }

    /**
     * Get API usage analytics.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getUsageAnalytics(int $businessId, array $filters = []): array
    {
        $query = ApiUsage::where('business_id', $businessId);

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $usage = $query->get();

        return [
            'total_requests' => $usage->count(),
            'by_endpoint' => $usage->groupBy('endpoint')->map->count(),
            'by_method' => $usage->groupBy('method')->map->count(),
            'by_status' => $usage->groupBy('response_code')->map->count(),
            'average_response_time' => 0, // Would need to track response time
        ];
    }
}