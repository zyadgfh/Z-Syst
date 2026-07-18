<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FraudDetectionService
{
    /**
     * Detect suspicious activities and return alerts
     */
    public function getFraudAlerts(int $companyId, int $days = 30): array
    {
        $cutoffDate = Carbon::now()->subDays($days);

        $alerts = [];

        // 1. Detect excessive cancellations
        $cancellationAlerts = $this->detectExcessiveCancellations($companyId, $cutoffDate);
        if (!empty($cancellationAlerts)) {
            $alerts = array_merge($alerts, $cancellationAlerts);
        }

        // 2. Detect excessive discounts
        $discountAlerts = $this->detectExcessiveDiscounts($companyId, $cutoffDate);
        if (!empty($discountAlerts)) {
            $alerts = array_merge($alerts, $discountAlerts);
        }

        // 3. Detect cash register anomalies
        $cashRegisterAlerts = $this->detectCashRegisterAnomalies($companyId, $cutoffDate);
        if (!empty($cashRegisterAlerts)) {
            $alerts = array_merge($alerts, $cashRegisterAlerts);
        }

        // 4. Detect unusual login patterns
        $loginAlerts = $this->detectUnusualLoginPatterns($companyId, $cutoffDate);
        if (!empty($loginAlerts)) {
            $alerts = array_merge($alerts, $loginAlerts);
        }

        return $alerts;
    }

    /**
     * Detect excessive cancellations by users
     */
    protected function detectExcessiveCancellations(int $companyId, Carbon $cutoffDate): array
    {
        $alerts = [];

        // Get all cancel sale activities
        $cancellations = ActivityLog::where('company_id', $companyId)
            ->where('action', 'like', '%cancel%')
            ->where('performed_at', '>=', $cutoffDate)
            ->get()
            ->groupBy('user_id');

        foreach ($cancellations as $userId => $userCancellations) {
            $count = $userCancellations->count();
            
            // Alert if more than 10 cancellations in the period
            if ($count > 10) {
                $user = User::find($userId);
                $alerts[] = [
                    'type' => 'excessive_cancellations',
                    'severity' => $count > 20 ? 'high' : 'medium',
                    'user_id' => $userId,
                    'user_name' => $user?->name ?? 'Unknown',
                    'count' => $count,
                    'message' => "User has {$count} cancellations in the last period, exceeding normal threshold.",
                    'detected_at' => now(),
                ];
            }
        }

        return $alerts;
    }

    /**
     * Detect excessive discounts on sales
     */
    protected function detectExcessiveDiscounts(int $companyId, Carbon $cutoffDate): array
    {
        $alerts = [];

        $sales = Sale::where('company_id', $companyId)
            ->where('created_at', '>=', $cutoffDate)
            ->get();

        $highDiscountSales = $sales->filter(function ($sale) {
            $discountPercent = $sale->totalAmount > 0 
                ? ($sale->discountAmount / $sale->totalAmount) * 100 
                : 0;
            return $discountPercent > 30; // Alert on >30% discount
        });

        if ($highDiscountSales->count() > 5) {
            $alerts[] = [
                'type' => 'excessive_discounts',
                'severity' => 'high',
                'count' => $highDiscountSales->count(),
                'total_discount_amount' => $highDiscountSales->sum('discountAmount'),
                'message' => "High discount transactions detected ({$highDiscountSales->count()} sales with >30% discount).",
                'detected_at' => now(),
            ];
        }

        return $alerts;
    }

    /**
     * Detect cash register anomalies (cash drawer discrepancies)
     */
    protected function detectCashRegisterAnomalies(int $companyId, Carbon $cutoffDate): array
    {
        $alerts = [];

        // Check for sales with unusual payment patterns
        $sales = Sale::where('company_id', $companyId)
            ->where('created_at', '>=', $cutoffDate)
            ->where('paymentType', 'cash')
            ->get();

        // Group by user and calculate average amounts
        $salesByUser = $sales->groupBy('user_id');
        
        foreach ($salesByUser as $userId => $userSales) {
            $avg = $userSales->avg('totalAmount');
            $stdDev = $this->calculateStdDev($userSales->pluck('totalAmount')->toArray());
            
            // Check for unusual large cash transactions
            $unusualSales = $userSales->filter(function ($sale) use ($avg, $stdDev) {
                return $stdDev > 0 && abs($sale->totalAmount - $avg) > ($stdDev * 3);
            });

            if ($unusualSales->count() > 3) {
                $user = User::find($userId);
                $alerts[] = [
                    'type' => 'cash_register_anomaly',
                    'severity' => ' medium',
                    'user_id' => $userId,
                    'user_name' => $user?->name ?? 'Unknown',
                    'count' => $unusualSales->count(),
                    'message' => "User has {$unusualSales->count()} unusual cash sales patterns.",
                    'detected_at' => now(),
                ];
            }
        }

        return $alerts;
    }

    /**
     * Detect unusual login patterns
     */
    protected function detectUnusualLoginPatterns(int $companyId, Carbon $cutoffDate): array
    {
        $alerts = [];

        $logins = ActivityLog::where('company_id', $companyId)
            ->where('action', 'like', '%login%')
            ->where('performed_at', '>=', $cutoffDate)
            ->get()
            ->groupBy('user_id');

        foreach ($logins as $userId => $userLogins) {
            $ips = $userLogins->pluck('ip_address')->unique()->toArray();
            
            // Alert if login from more than 5 IPs
            if (count($ips) > 5) {
                $user = User::find($userId);
                $alerts[] = [
                    'type' => 'multiple_ip_logins',
                    'severity' => 'high',
                    'user_id' => $userId,
                    'user_name' => $user?->name ?? 'Unknown',
                    'ip_count' => count($ips),
                    'ips' => $ips,
                    'message' => "User logged in from {$count($ips)} different IP addresses.",
                    'detected_at' => now(),
                ];
            }
        }

        return $alerts;
    }

    /**
     * Calculate standard deviation for fraud detection
     */
    protected function calculateStdDev(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0;
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / $count;

        return sqrt($variance);
    }

    /**
     * Get suspicious transactions report
     */
    public function getSuspiciousTransactions(int $companyId, array $filters = []): array
    {
        $query = ActivityLog::where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('action', 'like', '%cancel%')
                  ->orWhere('action', 'like', '%delete%')
                  ->orWhere('action', 'like', '%refund%')
                  ->orWhere('action', 'like', '%adjustment%');
            });

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('performed_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('performed_at', '<=', $filters['date_to']);
        }

        $activities = $query->with('user:id,name')
            ->orderByDesc('performed_at')
            ->paginate($filters['per_page'] ?? 25);

        return $activities->toArray();
    }

    /**
     * Log a suspicious activity
     */
    public function logSuspiciousActivity(
        int $companyId,
        int $userId,
        string $type,
        string $description,
        array $metadata = []
    ): ActivityLog {
        return ActivityLog::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'action' => "suspicious.{$type}",
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'performed_at' => now(),
            'properties' => $metadata,
        ]);
    }
}