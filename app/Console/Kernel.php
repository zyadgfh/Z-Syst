<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('demo:restore-public-images')->everyThirtyMinutes();
        
        // Expiry alerts - run daily at 8 AM
        $schedule->command('pharmacy:check-expiry --days=30')
            ->dailyAt('08:00')
            ->withoutOverlapping();
            
        // Expiry alerts for upcoming 60 and 90 days
        $schedule->command('pharmacy:check-expiry --days=60')
            ->dailyAt('08:30')
            ->withoutOverlapping();
            
        $schedule->command('pharmacy:check-expiry --days=90')
            ->dailyAt('09:00')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
