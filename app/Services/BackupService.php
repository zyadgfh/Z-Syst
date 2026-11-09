<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BackupService
{
    /**
     * Create database backup — Postgres (pg_dump) only. MySQL removed.
     */
    public function createDatabaseBackup(string $filename = null): string
    {
        $filename = $filename ?? 'backup_' . Carbon::now()->format('Y_m_d_His') . '.sql';
        // Sanitize filename
        $filename = basename($filename);
        if (! str_ends_with($filename, '.sql')) $filename .= '.sql';
        $path = storage_path('app/backups/' . $filename);

        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        $driver = DB::connection()->getDriverName();
        if ($driver !== 'pgsql') {
            throw new \Exception('Backup only supported on pgsql — current driver: '.$driver);
        }

        $dbConfig = config('database.connections.pgsql');
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -d %s --no-owner --no-privileges > %s',
            escapeshellarg($dbConfig['password'] ?? ''),
            escapeshellarg($dbConfig['host']),
            escapeshellarg((string) ($dbConfig['port'] ?? 5432)),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['database']),
            escapeshellarg($path)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Database backup failed (pg_dump exit '.$returnCode.')');
        }

        return $path;
    }

    public function createFullBackup(string $filename = null): array
    {
        $timestamp = Carbon::now()->format('Y_m_d_His');
        $backupName = $filename ? basename($filename) : "full_backup_{$timestamp}";
        
        $databaseBackup = $this->createDatabaseBackup("{$backupName}_database.sql");
        $filesBackup = $this->createFilesBackup($backupName);

        return [
            'database' => $databaseBackup,
            'files' => $filesBackup,
            'timestamp' => $timestamp,
        ];
    }

    public function createFilesBackup(string $backupName): string
    {
        $backupName = basename($backupName);
        $zipFile = storage_path('app/backups/' . $backupName . '_files.zip');
        
        if (!File::exists(dirname($zipFile))) {
            File::makeDirectory(dirname($zipFile), 0755, true);
        }

        $directories = [
            storage_path('app/public'),
            storage_path('app/uploads'),
        ];

        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
            foreach ($directories as $directory) {
                if (File::exists($directory)) {
                    $files = File::allFiles($directory);
                    foreach ($files as $file) {
                        $relativePath = str_replace(storage_path('app/'), '', $file->getPathname());
                        $zip->addFile($file->getPathname(), $relativePath);
                    }
                }
            }
            $zip->close();
        }

        return $zipFile;
    }

    public function restoreDatabase(string $backupPath): bool
    {
        $backupPath = basename($backupPath);
        $fullPath = storage_path('app/backups/' . $backupPath);
        if (!File::exists($fullPath)) {
            throw new \Exception('Backup file not found');
        }

        $driver = DB::connection()->getDriverName();
        if ($driver !== 'pgsql') {
            throw new \Exception('Restore only supported on pgsql');
        }

        $dbConfig = config('database.connections.pgsql');
        
        $command = sprintf(
            'PGPASSWORD=%s psql -h %s -p %s -U %s -d %s < %s',
            escapeshellarg($dbConfig['password'] ?? ''),
            escapeshellarg($dbConfig['host']),
            escapeshellarg((string) ($dbConfig['port'] ?? 5432)),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['database']),
            escapeshellarg($fullPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Database restore failed');
        }

        return true;
    }

    public function listBackups(): array
    {
        $backupDir = storage_path('app/backups');
        
        if (!File::exists($backupDir)) {
            return [];
        }

        $files = File::files($backupDir);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'filename' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $this->formatFileSize($file->getSize()),
                'modified' => Carbon::createFromTimestamp($file->getMTime())->toDateTimeString(),
            ];
        }

        return array_reverse($backups);
    }

    public function deleteOldBackups(int $daysToKeep = 30): int
    {
        $backupDir = storage_path('app/backups');
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        if (!File::exists($backupDir)) {
            return 0;
        }

        $files = File::files($backupDir);
        $deletedCount = 0;

        foreach ($files as $file) {
            if (Carbon::createFromTimestamp($file->getMTime())->lt($cutoffDate)) {
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    public function getBackupSize(): string
    {
        $backupDir = storage_path('app/backups');
        
        if (!File::exists($backupDir)) {
            return '0 B';
        }

        $size = 0;
        foreach (File::allFiles($backupDir) as $f) $size += $f->getSize();
        return $this->formatFileSize($size);
    }

    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function scheduleBackup(): void
    {
        try {
            $backup = $this->createFullBackup();
            $this->deleteOldBackups(config('backup.retention_days', 30));
            \Log::info('Backup created successfully', $backup);
        } catch (\Exception $e) {
            \Log::error('Backup failed', ['error' => $e->getMessage()]);
        }
    }

    public function downloadBackup(string $filename)
    {
        $filename = basename($filename);
        if (str_contains($filename, '..') || str_contains($filename, '/')) abort(400, 'Invalid filename');
        $path = storage_path('app/backups/' . $filename);
        
        if (!File::exists($path)) {
            throw new \Exception('Backup file not found');
        }

        return response()->download($path);
    }

    public function deleteBackup(string $filename): bool
    {
        $filename = basename($filename);
        if (str_contains($filename, '..') || str_contains($filename, '/')) return false;
        $path = storage_path('app/backups/' . $filename);
        
        if (!File::exists($path)) {
            return false;
        }

        return File::delete($path);
    }
}
