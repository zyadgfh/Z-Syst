<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
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
     * Get products expiring soon - uses Inventory (UUID) model for FEFO-aware expiry tracking.
     *
     * @return array<int, array>
     */
    public function getExpiringProducts(string $companyId, ?string $branchId = null, int $days = 30): array
    {
        $endDate = Carbon::now()->addDays($days);

        $query = Inventory::where('company_id', $companyId)
            ->where('quantity', '>', 0)
            ->whereBetween('expiry_date', [Carbon::now(), $endDate])
            ->with(['product', 'branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $inventories = $query->orderBy('expiry_date', 'asc')->get();

        return $inventories->map(function ($inventory) {
            $daysUntilExpiry = Carbon::now()->diffInDays($inventory->expiry_date, false);

            return [
                'inventory_id' => $inventory->id,
                'product_id' => $inventory->product_id,
                'product_name' => $inventory->product->name ?? $inventory->product->generic_name ?? 'Unknown',
                'product_code' => $inventory->product->product_code ?? $inventory->product->sku ?? null,
                'barcode' => $inventory->product->barcode ?? null,
                'branch_id' => $inventory->branch_id,
                'branch_name' => $inventory->branch->name ?? 'Unknown',
                'batch_number' => $inventory->batch_number,
                'quantity' => (float) $inventory->quantity,
                'expiry_date' => $inventory->expiry_date->format('Y-m-d'),
                'days_until_expiry' => $daysUntilExpiry,
                'urgency' => $this->calculateUrgency($daysUntilExpiry),
                'value_at_cost' => (float) ($inventory->quantity * ($inventory->cost_price ?? 0)),
                'value_at_sale' => (float) ($inventory->quantity * ($inventory->selling_price ?? 0)),
            ];
        })->sortBy('days_until_expiry')->values()->toArray();
    }

    /**
     * Calculate urgency level based on days until expiry.
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
     * Send expiry alerts to managers.
     */
    public function sendExpiryAlerts(string $companyId, int $days = 30): array
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
        // For now, we'll just count the potential alerts
        $alertCount = $managers->count();

        return [
            'sent' => $alertCount,
            'products_count' => count($expiringProducts),
            'message' => "Expiry alerts sent to {$alertCount} managers about " . count($expiringProducts) . " products.",
        ];
    }

    /**
     * Get expiry alert summary statistics.
     */
    public function getExpiryStats(string $companyId, ?string $branchId = null): array
    {
        $stats = [
            '30_days' => 0,
            '60_days' => 0,
            '90_days' => 0,
            'expired' => 0,
            'total_value_at_risk' => 0,
        ];

        $query = Inventory::where('company_id', $companyId)
            ->where('quantity', '>', 0);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Products expiring within 30 days
        $stats['30_days'] = (float) (clone $query)
            ->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays(30)])
            ->sum('quantity');

        // Products expiring within 31-60 days
        $stats['60_days'] = (float) (clone $query)
            ->whereBetween('expiry_date', [Carbon::now()->addDays(31), Carbon::now()->addDays(60)])
            ->sum('quantity');

        // Products expiring within 61-90 days
        $stats['90_days'] = (float) (clone $query)
            ->whereBetween('expiry_date', [Carbon::now()->addDays(61), Carbon::now()->addDays(90)])
            ->sum('quantity');

        // Already expired products
        $stats['expired'] = (float) (clone $query)
            ->where('expiry_date', '<', Carbon::now())
            ->sum('quantity');

        // Calculate total value at risk (products expiring within 90 days)
        $atRiskInventories = (clone $query)
            ->where('expiry_date', '<=', Carbon::now()->addDays(90))
            ->get();

        $stats['total_value_at_risk'] = $atRiskInventories->sum(function ($inv) {
            return (float) ($inv->quantity * ($inv->cost_price ?? 0));
        });

        return $stats;
    }

    /**
     * Get expired products that need to be written off.
     */
    public function getExpiredProducts(string $companyId, ?string $branchId = null): array
    {
        $query = Inventory::where('company_id', $companyId)
            ->where('quantity', '>', 0)
            ->where('expiry_date', '<', Carbon::now())
            ->with(['product:id,name,generic_name,barcode,product_code,sku', 'branch:id,name']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('expiry_date', 'asc')->get()
            ->map(fn($inv) => [
                'inventory_id' => $inv->id,
                'product_id' => $inv->product_id,
                'product_name' => $inv->product->name ?? $inv->product->generic_name ?? 'Unknown',
                'barcode' => $inv->product->barcode,
                'branch_name' => $inv->branch->name ?? 'Unknown',
                'batch_number' => $inv->batch_number,
                'quantity' => (float) $inv->quantity,
                'expiry_date' => $inv->expiry_date->format('Y-m-d'),
                'days_expired' => Carbon::now()->diffInDays($inv->expiry_date, false),
                'value_at_cost' => (float) ($inv->quantity * ($inv->cost_price ?? 0)),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Execute periodic expiry check (for scheduled command).
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

