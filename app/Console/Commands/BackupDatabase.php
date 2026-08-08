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
    protected $signature = 'backup:database';

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
            
            $path = $backupService->createDatabaseBackup();
            
            $this->info("Database backup created successfully: {$path}");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            
            return Command::FAILURE;
        }
    }
}
