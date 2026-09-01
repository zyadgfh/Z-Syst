<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Notification;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExpiryAlertService
{
    use WithTransactionalOperations;

    /**
     * Generate expiry alerts for a business.
     *
     * @param int $businessId
     * @param array<int> $thresholds // Days thresholds (e.g., [7, 30, 60, 90])
     * @return array
     */
    public function generateExpiryAlerts(int $businessId, array $thresholds = [7, 30, 60, 90]): array
    {
        $alerts = [];

        foreach ($thresholds as $days) {
            $batches = $this->getBatchesExpiringInDays($businessId, $days);
            
            if ($batches->isNotEmpty()) {
                $alerts[$days] = [
                    'threshold_days' => $days,
                    'count' => $batches->count(),
                    'total_quantity' => $batches->sum('productStock'),
                    'total_value' => $this->calculateBatchValue($batches),
                    'batches' => $batches,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Send expiry notifications for a business.
     *
     * @param int $businessId
     * @param int $userId
     * @param array<int> $thresholds
     * @return int
     */
    public function sendExpiryNotifications(int $businessId, int $userId, array $thresholds = [7, 30, 60, 90]): int
    {
        $alerts = $this->generateExpiryAlerts($businessId, $thresholds);
        $notificationsSent = 0;

        foreach ($alerts as $days => $alertData) {
            $notification = Notification::create([
                'business_id' => $businessId,
                'user_id' => $userId,
                'type' => 'expiry_alert',
                'title' => "Stock Expiring in {$days} Days",
                'message' => $this->formatExpiryAlertMessage($alertData),
                'data' => json_encode($alertData),
                'read' => false,
            ]);

            if ($notification) {
                $notificationsSent++;
            }
        }

        return $notificationsSent;
    }

    /**
     * Get comprehensive expiry report.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getExpiryReport(int $businessId, array $filters = []): array
    {
        $query = Stock::where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->whereNotNull('expire_date')
            ->with('product:id,productName,purchase_without_tax,sales_price');

        // Apply filters
        if (isset($filters['from_date'])) {
            $query->where('expire_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('expire_date', '<=', $filters['to_date']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['category_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        $batches = $query->orderBy('expire_date', 'asc')->get();

        // Categorize batches
        $now = now();
        $report = [
            'total_batches' => $batches->count(),
            'total_quantity' => $batches->sum('productStock'),
            'total_value' => $this->calculateBatchValue($batches),
            'categories' => [
                'expired' => [
                    'count' => 0,
                    'quantity' => 0,
                    'value' => 0,
                    'batches' => collect(),
                ],
                'critical' => [ // 0-7 days
                    'count' => 0,
                    'quantity' => 0,
                    'value' => 0,
                    'batches' => collect(),
                ],
                'warning' => [ // 8-30 days
                    'count' => 0,
                    'quantity' => 0,
                    'value' => 0,
                    'batches' => collect(),
                ],
                'caution' => [ // 31-90 days
                    'count' => 0,
                    'quantity' => 0,
                    'value' => 0,
                    'batches' => collect(),
                ],
                'safe' => [ // 90+ days
                    'count' => 0,
                    'quantity' => 0,
                    'value' => 0,
                    'batches' => collect(),
                ],
            ],
        ];

        foreach ($batches as $batch) {
            $daysUntilExpiry = $now->diffInDays(Carbon::parse($batch->expire_date), false);
            $batchValue = ($batch->product->purchase_without_tax ?? 0) * $batch->productStock;

            if ($daysUntilExpiry < 0) {
                $report['categories']['expired']['count']++;
                $report['categories']['expired']['quantity'] += $batch->productStock;
                $report['categories']['expired']['value'] += $batchValue;
                $report['categories']['expired']['batches']->push($batch);
            } elseif ($daysUntilExpiry <= 7) {
                $report['categories']['critical']['count']++;
                $report['categories']['critical']['quantity'] += $batch->productStock;
                $report['categories']['critical']['value'] += $batchValue;
                $report['categories']['critical']['batches']->push($batch);
            } elseif ($daysUntilExpiry <= 30) {
                $report['categories']['warning']['count']++;
                $report['categories']['warning']['quantity'] += $batch->productStock;
                $report['categories']['warning']['value'] += $batchValue;
                $report['categories']['warning']['batches']->push($batch);
            } elseif ($daysUntilExpiry <= 90) {
                $report['categories']['caution']['count']++;
                $report['categories']['caution']['quantity'] += $batch->productStock;
                $report['categories']['caution']['value'] += $batchValue;
                $report['categories']['caution']['batches']->push($batch);
            } else {
                $report['categories']['safe']['count']++;
                $report['categories']['safe']['quantity'] += $batch->productStock;
                $report['categories']['safe']['value'] += $batchValue;
                $report['categories']['safe']['batches']->push($batch);
            }
        }

        return $report;
    }

    /**
     * Get batches expiring in specific days range.
     *
     * @param int $businessId
     * @param int $days
     * @return Collection
     */
    protected function getBatchesExpiringInDays(int $businessId, int $days): Collection
    {
        $expiryDate = Carbon::now()->addDays($days);

        return Stock::where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->whereNotNull('expire_date')
            ->where('expire_date', '<=', $expiryDate)
            ->where('expire_date', '>=', now())
            ->with('product:id,productName,purchase_without_tax')
            ->orderBy('expire_date', 'asc')
            ->get();
    }

    /**
     * Calculate total value of batches.
     *
     * @param Collection $batches
     * @return float
     */
    protected function calculateBatchValue(Collection $batches): float
    {
        return $batches->sum(function ($batch) {
            return ($batch->product->purchase_without_tax ?? 0) * $batch->productStock;
        });
    }

    /**
     * Format expiry alert message.
     *
     * @param array $alertData
     * @return string
     */
    protected function formatExpiryAlertMessage(array $alertData): string
    {
        return sprintf(
            "%d product batches expiring in %d days. Total quantity: %d, Estimated value: %.2f",
            $alertData['count'],
            $alertData['threshold_days'],
            $alertData['total_quantity'],
            $alertData['total_value']
        );
    }

    /**
     * Get low stock alerts (separate from expiry).
     *
     * @param int $businessId
     * @param int|null $threshold
     * @return Collection
     */
    public function getLowStockAlerts(int $businessId, ?int $threshold = null): Collection
    {
        $defaultThreshold = $threshold ?? 10;

        return Stock::where('business_id', $businessId)
            ->where('productStock', '<=', $defaultThreshold)
            ->where('productStock', '>', 0)
            ->with('product:id,productName,alert_qty')
            ->get()
            ->filter(function ($stock) use ($defaultThreshold) {
                $alertThreshold = $stock->product->alert_qty ?? $defaultThreshold;
                return $stock->productStock <= $alertThreshold;
            });
    }

    /**
     * Send low stock notifications.
     *
     * @param int $businessId
     * @param int $userId
     * @param int|null $threshold
     * @return int
     */
    public function sendLowStockNotifications(int $businessId, int $userId, ?int $threshold = null): int
    {
        $lowStockItems = $this->getLowStockAlerts($businessId, $threshold);
        
        if ($lowStockItems->isEmpty()) {
            return 0;
        }

        $notification = Notification::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'type' => 'low_stock_alert',
            'title' => 'Low Stock Alert',
            'message' => sprintf(
                "%d products are running low on stock. Immediate replenishment recommended.",
                $lowStockItems->count()
            ),
            'data' => json_encode([
                'count' => $lowStockItems->count(),
                'items' => $lowStockItems->map(function ($stock) {
                    return [
                        'product_id' => $stock->product_id,
                        'product_name' => $stock->product->productName,
                        'current_stock' => $stock->productStock,
                        'alert_threshold' => $stock->product->alert_qty ?? 10,
                    ];
                }),
            ]),
            'read' => false,
        ]);

        return $notification ? 1 : 0;
    }

    /**
     * Get combined inventory health report.
     *
     * @param int $businessId
     * @return array
     */
    public function getInventoryHealthReport(int $businessId): array
    {
        $expiryReport = $this->getExpiryReport($businessId);
        $lowStockAlerts = $this->getLowStockAlerts($businessId);

        return [
            'expiry_report' => $expiryReport,
            'low_stock_alerts' => [
                'count' => $lowStockAlerts->count(),
                'items' => $lowStockAlerts,
            ],
            'health_score' => $this->calculateHealthScore($expiryReport, $lowStockAlerts),
            'recommendations' => $this->generateRecommendations($expiryReport, $lowStockAlerts),
        ];
    }

    /**
     * Calculate inventory health score (0-100).
     *
     * @param array $expiryReport
     * @param Collection $lowStockAlerts
     * @return int
     */
    protected function calculateHealthScore(array $expiryReport, Collection $lowStockAlerts): int
    {
        $score = 100;

        // Deduct for expired items
        $expiredCount = $expiryReport['categories']['expired']['count'];
        $score -= min($expiredCount * 5, 30); // Max 30 points deduction

        // Deduct for critical expiry items
        $criticalCount = $expiryReport['categories']['critical']['count'];
        $score -= min($criticalCount * 2, 20); // Max 20 points deduction

        // Deduct for low stock items
        $lowStockCount = $lowStockAlerts->count();
        $score -= min($lowStockCount * 1, 20); // Max 20 points deduction

        return max(0, $score);
    }

    /**
     * Generate recommendations based on inventory status.
     *
     * @param array $expiryReport
     * @param Collection $lowStockAlerts
     * @return array<string>
     */
    protected function generateRecommendations(array $expiryReport, Collection $lowStockAlerts): array
    {
        $recommendations = [];

        if ($expiryReport['categories']['expired']['count'] > 0) {
            $recommendations[] = "Immediately remove or dispose of {$expiryReport['categories']['expired']['count']} expired batches.";
        }

        if ($expiryReport['categories']['critical']['count'] > 0) {
            $recommendations[] = "Prioritize sales of {$expiryReport['categories']['critical']['count']} batches expiring within 7 days.";
        }

        if ($expiryReport['categories']['warning']['count'] > 0) {
            $recommendations[] = "Consider discounts for {$expiryReport['categories']['warning']['count']} batches expiring within 30 days.";
        }

        if ($lowStockAlerts->count() > 0) {
            $recommendations[] = "Replenish stock for {$lowStockAlerts->count()} products with low inventory.";
        }

        if (empty($recommendations)) {
            $recommendations[] = "Inventory health is good. Continue regular monitoring.";
        }

        return $recommendations;
    }
}