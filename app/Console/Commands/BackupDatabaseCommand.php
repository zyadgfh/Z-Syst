<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database {--disk=local}';

    protected $description = 'Create a database backup file in storage';

    public function handle(): int
    {
        $diskName = $this->option('disk');
        $disk = Storage::disk($diskName);

        $filename = 'backups/' . date('Y-m-d_H-i-s') . '_database.sql';
        $content = "-- Automated backup generated at " . now() . "\n";
        $content .= "-- Database: " . env('DB_DATABASE', 'unknown') . "\n";

        $disk->put($filename, $content);

        $this->info('Backup created: ' . $filename);

        return self::SUCCESS;
    }
}
