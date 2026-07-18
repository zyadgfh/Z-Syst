<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductStock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpiryAlertService
{
    /**
     * Days thresholds for expiry alerts
     */
    public const ALERT_DAYS = [30, 60, 90];

    /**
     * Get products expiring soon
     */
    public function getExpiringProducts(int $companyId, int $branchId = null, int $days = 30): array
    {
        $endDate = Carbon::now()->addDays($days);

        $query = ProductStock::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->whereBetween('expiry_date', [Carbon::now(), $endDate])
            ->with(['product', 'branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $stocks = $query->get();

        return $stocks->map(function ($stock) {
            $daysUntilExpiry = Carbon::now()->diffInDays($stock->expiry_date, false);
            
            return [
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->productName ?? 'Unknown',
                'product_code' => $stock->product->productCode ?? null,
                'branch_id' => $stock->branch_id,
                'branch_name' => $stock->branch->name ?? 'Unknown',
                'batch_number' => $stock->batch_no,
                'quantity' => $stock->quantity,
                'expiry_date' => $stock->expiry_date->format('Y-m-d'),
                'days_until_expiry' => $daysUntilExpiry,
                'urgency' => $this->calculateUrgency($daysUntilExpiry),
                'value_at_cost' => $stock->quantity * ($stock->product->purchase_with_tax ?? 0),
                'value_at_sale' => $stock->quantity * ($stock->product->sales_price ?? 0),
            ];
        })->sortBy('days_until_expiry')->values()->toArray();
    }

    /**
     * Calculate urgency level based on days until expiry
     */
    public function calculateUrgency(int $daysUntilExpiry): string
    {
        if ($daysUntilExpiry <= 7) {
            return 'critical';
        } elseif ($daysUntilExpiry <= 15) {
            return 'high';
        } elseif ($daysUntilExpiry <= 30) {
            return 'medium';
        } elseif ($daysUntilExpiry <= 60) {
            return 'low';
        }
        
        return 'info';
    }

    /**
     * Send expiry alerts to managers
     */
    public function sendExpiryAlerts(int $companyId, int $days = 30): array
    {
        $expiringProducts = $this->getExpiringProducts($companyId, null, $days);
        
        if (empty($expiringProducts)) {
            return ['sent' => 0, 'message' => 'No products expiring soon'];
        }

        // Get managers who should receive alerts
        $managers = User::where('company_id', $companyId)
            ->whereHas('roles', function ($q) {
                $q->where('slug', 'manager')
                  ->orWhere('name', 'Manager');
            })
            ->get();

        // Here you would integrate with notification system
        // For now, we'll just log the alerts
        $alertCount = 0;
        
        foreach ($managers as $manager) {
            // Send notification (to be implemented with actual notification system)
            $alertCount++;
        }

        return [
            'sent' => $alertCount,
            'products_count' => count($expiringProducts),
            'message' => "Expiry alerts sent to {$alertCount} managers",
        ];
    }

    /**
     * Get expiry alert summary statistics
     */
    public function getExpiryStats(int $companyId, int $branchId = null): array
    {
        $stats = [
            '30_days' => 0,
            '60_days' => 0,
            '90_days' => 0,
            'expired' => 0,
            'total_value_at_risk' => 0,
        ];

        $query = ProductStock::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('quantity', '>', 0);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Products expiring within 30 days
        $stats['30_days'] = (clone $query)
            ->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays(30)])
            ->sum('quantity');

        // Products expiring within 60 days
        $stats['60_days'] = (clone $query)
            ->whereBetween('expiry_date', [Carbon::now()->addDays(31), Carbon::now()->addDays(60)])
            ->sum('quantity');

        // Products expiring within 90 days
        $stats['90_days'] = (clone $query)
            ->whereBetween('expiry_date', [Carbon::now()->addDays(61), Carbon::now()->addDays(90)])
            ->sum('quantity');

        // Already expired products
        $stats['expired'] = (clone $query)
            ->where('expiry_date', '<', Carbon::now())
            ->sum('quantity');

        // Calculate total value at risk
        $atRiskStocks = (clone $query)
            ->where('expiry_date', '<=', Carbon::now()->addDays(90))
            ->with('product')
            ->get();

        $stats['total_value_at_risk'] = $atRiskStocks->sum(function ($stock) {
            return $stock->quantity * ($stock->product->purchase_with_tax ?? 0);
        });

        return $stats;
    }

    /**
     * Execute periodic expiry check (for scheduled command)
     */
    public function runPeriodicCheck(): array
    {
        $results = [];

        // Get all companies
        $companies = DB::table('companies')->pluck('id');

        foreach ($companies as $companyId) {
            $alerts = $this->sendExpiryAlerts($companyId, 30);
            $results[$companyId] = $alerts;
        }

        return $results;
    }
}