<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BackupService
{
    /**
     * Create database backup
     */
    public function createDatabaseBackup(string $filename = null): string
    {
        $filename = $filename ?? 'backup_' . Carbon::now()->format('Y_m_d_His') . '.sql';
        $path = storage_path('app/backups/' . $filename);

        // Ensure backup directory exists
        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        // Get database configuration
        $dbConfig = config('database.connections.mysql');
        
        // Build mysqldump command
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s',
            $dbConfig['host'],
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['database'],
            $path
        );

        // Execute backup
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Database backup failed');
        }

        return $path;
    }

    /**
     * Create full backup (database + files)
     */
    public function createFullBackup(string $filename = null): array
    {
        $timestamp = Carbon::now()->format('Y_m_d_His');
        $backupName = $filename ?? "full_backup_{$timestamp}";
        
        $databaseBackup = $this->createDatabaseBackup("{$backupName}_database.sql");
        $filesBackup = $this->createFilesBackup($backupName);

        return [
            'database' => $databaseBackup,
            'files' => $filesBackup,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Create files backup
     */
    public function createFilesBackup(string $backupName): string
    {
        $zipFile = storage_path('app/backups/' . $backupName . '_files.zip');
        
        // Ensure backup directory exists
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

    /**
     * Restore database from backup
     */
    public function restoreDatabase(string $backupPath): bool
    {
        if (!File::exists($backupPath)) {
            throw new \Exception('Backup file not found');
        }

        $dbConfig = config('database.connections.mysql');
        
        $command = sprintf(
            'mysql -h%s -u%s -p%s %s < %s',
            $dbConfig['host'],
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['database'],
            $backupPath
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Database restore failed');
        }

        return true;
    }

    /**
     * List all backups
     */
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

        return array_reverse($backups); // Newest first
    }

    /**
     * Delete old backups
     */
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

    /**
     * Get backup size
     */
    public function getBackupSize(): string
    {
        $backupDir = storage_path('app/backups');
        
        if (!File::exists($backupDir)) {
            return '0 B';
        }

        $size = File::size($backupDir);
        return $this->formatFileSize($size);
    }

    /**
     * Format file size
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Schedule automatic backup
     */
    public function scheduleBackup(): void
    {
        // This should be called from a scheduled command
        try {
            $backup = $this->createFullBackup();
            
            // Clean old backups
            $this->deleteOldBackups(config('backup.retention_days', 30));
            
            \Log::info('Backup created successfully', $backup);
        } catch (\Exception $e) {
            \Log::error('Backup failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Download backup
     */
    public function downloadBackup(string $filename)
    {
        $path = storage_path('app/backups/' . $filename);
        
        if (!File::exists($path)) {
            throw new \Exception('Backup file not found');
        }

        return response()->download($path);
    }

    /**
     * Delete specific backup
     */
    public function deleteBackup(string $filename): bool
    {
        $path = storage_path('app/backups/' . $filename);
        
        if (!File::exists($path)) {
            return false;
        }

        return File::delete($path);
    }
}
