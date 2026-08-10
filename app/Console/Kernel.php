<?php

namespace App\Console;

use App\Console\Commands\BackupDatabase;
use App\Console\Commands\CalculateDoctorAttentionScores;
use App\Console\Commands\ManageSubscriptions;
use App\Console\Commands\RotateSettingsKey;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        RotateSettingsKey::class,
        BackupDatabase::class,
        CalculateDoctorAttentionScores::class,
        ManageSubscriptions::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('expiry-alert:send')->dailyAt('08:00');
        $schedule->command('backup:database')->dailyAt('02:00');
        $schedule->command('doctor-attention:calculate')->dailyAt('00:00');
        $schedule->command('subscriptions:manage')->dailyAt('01:00');
        $schedule->command('db:update-stats')->dailyAt('03:00');
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
