<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\AnomalyDetector;
use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

class AnomalyScanCommand extends Command
{
    protected $signature = 'anomalies:scan {--business= : business id}';
    protected $description = 'Scan for financial anomalies (returns, drawer variance, discount spikes) and alert';

    public function handle(AnomalyDetector $detector, TelegramNotifier $telegram): int
    {
        $businesses = $this->option('business')
            ? Business::whereKey((int) $this->option('business'))->get()
            : Business::all();

        foreach ($businesses as $business) {
            $found = $detector->scan((int) $business->id);
            $total = array_sum($found);
            $this->info("business {$business->id}: $total anomaly alerts");
            if ($total > 0) {
                $telegram->send((int) $business->id, "🚨 رصد $total تنبيه شذوذ مالي — راجع لوحة التحليلات.");
            }
        }

        return self::SUCCESS;
    }
}
