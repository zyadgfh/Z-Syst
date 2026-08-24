<?php

namespace App\Console\Commands;

use App\Events\InventoryAlertCreated;
use App\Models\InventoryAlert;
use App\Models\User;
use App\Notifications\InventoryCriticalAlertNotification;
use Illuminate\Console\Command;

class ScanInventoryAlerts extends Command
{
    protected $signature = 'inventory:scan {--business= : Run scan for specific business ID only}';
    protected $description = 'Scan all products and generate inventory alerts (low stock, out of stock, expiring)';

    public function handle(): int
    {
        $this->info('🔍 Starting inventory scan...');

        $businessId = $this->option('business') ? (int) $this->option('business') : null;

        $created = InventoryAlert::runInventoryScan($businessId);

        $this->info("✅ Scan complete — {$created} new alert(s) generated.");

        // Broadcast critical alerts in real-time
        InventoryAlert::unacknowledged()
            ->where('severity', 'critical')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->each(function ($alert) {
                broadcast(new InventoryAlertCreated($alert));
            });

        // Send email notifications for critical unacknowledged alerts
        $criticalAlerts = InventoryAlert::unacknowledged()
            ->where('severity', 'critical')
            ->where('created_at', '>=', now()->subHour()) // Only recent ones
            ->with('product')
            ->get();

        if ($criticalAlerts->isNotEmpty()) {
            $this->info("📧 Sending critical alert notifications...");

            // Get admin users to notify
            $admins = User::whereIn('role', ['admin', 'superadmin'])
                ->where('status', 1)
                ->get();

            foreach ($admins as $admin) {
                try {
                    $admin->notify(new InventoryCriticalAlertNotification($criticalAlerts));
                    $this->info("  → Notified: {$admin->name}");
                } catch (\Throwable $e) {
                    $this->error("  → Failed to notify {$admin->name}: {$e->getMessage()}");
                }
            }
        }

        // Log summary
        $stats = [
            'critical'     => InventoryAlert::unacknowledged()->where('severity', 'critical')->count(),
            'warning'      => InventoryAlert::unacknowledged()->where('severity', 'warning')->count(),
            'low_stock'    => InventoryAlert::unacknowledged()->where('type', 'low_stock')->count(),
            'out_of_stock' => InventoryAlert::unacknowledged()->where('type', 'out_of_stock')->count(),
            'expiring'     => InventoryAlert::unacknowledged()->whereIn('type', ['expiring_soon', 'expired'])->count(),
        ];

        $this->table(['Metric', 'Count'], [
            ['Critical Alerts', $stats['critical']],
            ['Warning Alerts', $stats['warning']],
            ['Low Stock', $stats['low_stock']],
            ['Out of Stock', $stats['out_of_stock']],
            ['Expiring/Expired', $stats['expiring']],
        ]);

        return Command::SUCCESS;
    }
}
