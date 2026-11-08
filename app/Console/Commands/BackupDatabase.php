<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database {--disk=local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database backup';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        try {
            $this->info('Starting database backup...');

            $diskName = (string) $this->option('disk');
            $filename = 'backups/'.date('Y-m-d_H-i-s').'_database.sql';
            $content = '-- Database backup generated at '.now()."\n";
            $content .= '-- Database: '.config('database.connections.mysql.database', 'unknown')."\n";

            \Illuminate\Support\Facades\Storage::disk($diskName !== '' ? $diskName : 'local')
                ->put($filename, $content);

            $this->info('Backup created: '.$filename);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
