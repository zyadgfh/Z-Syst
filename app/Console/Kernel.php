<?php

namespace App\Console;

use App\Console\Commands\RotateSettingsKey;
use App\Console\Commands\BackupDatabase;
use App\Console\Commands\CalculateDoctorAttentionScores;
use App\Console\Commands\ManageSubscriptions;
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
        // sales operations unit
        $schedule->command('forecast:generate')->dailyAt('00:30');
        $schedule->command('anomalies:scan')->dailyAt('23:30');
        $schedule->command('telegram:daily')->dailyAt('22:00');
        $schedule->command('patients:refill')->dailyAt('09:00');
        $schedule->command('eta:retry')->everyFifteenMinutes();
        // database queue runner (no redis needed)
        $schedule->command('queue:work', ['--stop-when-empty', '--max-time=120'])->everyMinute()->withoutOverlapping();
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
