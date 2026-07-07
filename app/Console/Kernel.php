<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Check branch limits daily at midnight
        $schedule->command('branches:check-limits')->dailyAt('00:00');

        // Calculate daily analytics metrics
        $schedule->command('analytics:calculate-daily-metrics')->dailyAt('01:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
