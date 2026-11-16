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
            if ($this->option('disk') !== 'local') {
                throw new \InvalidArgumentException('Only private local backup storage is supported');
            }
            $this->info('Starting database backup...');

            $path = $backupService->createDatabaseBackup();

            $checksum = hash_file('sha256', $path);
            if ($checksum === false) {
                throw new \RuntimeException('Unable to hash the database backup');
            }
            $this->info("Backup created: {$path} (SHA-256: {$checksum})");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
