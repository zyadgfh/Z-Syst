<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class BackupService
{
    /**
     * Create database backup
     */
    public function createDatabaseBackup(?string $filename = null): string
    {
        $filename = $filename ?? 'backup_'.Carbon::now()->format('Y_m_d_His').'.sql';
        $path = storage_path('app/backups/'.$filename);

        // Ensure backup directory exists
        if (! File::exists(dirname($path))) {
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
    public function createFullBackup(?string $filename = null): array
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
        $zipFile = storage_path('app/backups/'.$backupName.'_files.zip');

        // Ensure backup directory exists
        if (! File::exists(dirname($zipFile))) {
            File::makeDirectory(dirname($zipFile), 0755, true);
        }

        $directories = [
            storage_path('app/public'),
            storage_path('app/uploads'),
        ];

        $zip = new \ZipArchive;
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
        if (! File::exists($backupPath)) {
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

        if (! File::exists($backupDir)) {
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

        if (! File::exists($backupDir)) {
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

        if (! File::exists($backupDir)) {
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

        return round($bytes, 2).' '.$units[$i];
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
        $path = storage_path('app/backups/'.$filename);

        if (! File::exists($path)) {
            throw new \Exception('Backup file not found');
        }

        return response()->download($path);
    }

    /**
     * Delete specific backup
     */
    public function deleteBackup(string $filename): bool
    {
        $path = storage_path('app/backups/'.$filename);

        if (! File::exists($path)) {
            return false;
        }

        return File::delete($path);
    }

    /**
     * Import (restore) database from an uploaded SQL backup file.
     *
     * Supports plain (.sql) and gzipped (.sql.gz) files.
     * Writes the resolved SQL to a temp file, imports via the `mysql`
     * CLI binary, and cleans up on completion.
     *
     * @param  string  $uploadedPath  Temporary path of the uploaded file
     * @return array  Result metadata (success, message, stats)
     *
     * @throws \Exception
     */
    public function importBackup(string $uploadedPath): array
    {
        if (! File::exists($uploadedPath)) {
            throw new \Exception('Backup file not found.');
        }

        // 1. Validate the uploaded file
        $this->validateBackupFile($uploadedPath);

        // 2. Decompress if gzipped
        $sqlPath = $this->prepareReadableSql($uploadedPath);

        try {
            // 3. Import via mysql CLI
            $stats = $this->importSqlFile($sqlPath);

            AuditLogger::log('backup.imported', 'Database restored from uploaded backup file.', [
                'file'     => basename($uploadedPath),
                'rows'     => $stats['rows'] ?? 0,
                'duration' => $stats['duration'] ?? 0,
            ]);

            return [
                'success' => true,
                'message' => __('Database imported successfully.'),
                'stats'   => $stats,
            ];
        } finally {
            // Always clean up temp files that are not original uploads
            if ($sqlPath !== $uploadedPath && File::exists($sqlPath)) {
                File::delete($sqlPath);
            }
        }
    }

    /**
     * Validate that an uploaded file is a safe SQL/GZ backup.
     */
    protected function validateBackupFile(string $path): void
    {
        $allowedMimes = ['sql', 'gz'];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, $allowedMimes)) {
            throw new \Exception('Invalid file type. Only .sql and .sql.gz files are allowed.');
        }

        // Guard against huge uploads
        $maxBytes = (int) ini_get('upload_max_filesize');
        if ($maxBytes === 0) {
            $maxBytes = 64 * 1024 * 1024; // 64 MB fallback
        }

        if (File::size($path) > $maxBytes) {
            throw new \Exception('Backup file is too large.');
        }
    }

    /**
     * Returns a readable SQL path, decompressing .gz files to a temp file first.
     */
    protected function prepareReadableSql(string $uploadedPath): string
    {
        if (str_ends_with(strtolower($uploadedPath), '.gz')) {
            $tempPath = tempnam(sys_get_temp_dir(), 'backup_import_').'.sql';

            $source = gzopen($uploadedPath, 'rb');
            $target = fopen($tempPath, 'wb');

            if ($source === false || $target === false) {
                throw new \Exception('Failed to open archive for reading.');
            }

            while (! gzeof($source)) {
                fwrite($target, gzread($source, 8192));
            }

            fclose($target);
            gzclose($source);

            return $tempPath;
        }

        return $uploadedPath;
    }

    /**
     * Execute the mysql import and collect execution stats.
     */
    protected function importSqlFile(string $sqlPath): array
    {
        $dbConfig = config('database.connections.mysql');

        // Build the mysql command. Password passed via pipe to avoid leaking
        // in process list / shell history.
        $command = sprintf(
            'mysql -h%s -u%s -p%s %s --force < %s 2>&1',
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbConfig['database']),
            escapeshellarg($sqlPath)
        );

        $start = microtime(true);
        exec($command, $output, $returnCode);
        $duration = round(microtime(true) - $start, 2);

        if ($returnCode !== 0) {
            throw new \Exception(
                'Database import failed: '.implode("\n", $output)
            );
        }

        // Try to extract affected-rows info from the output
        $rows = 0;
        foreach ($output as $line) {
            if (preg_match('/^(\d+)\s+row[s]?\s+affect/i', $line, $m)) {
                $rows += (int) $m[1];
            }
        }

        return [
            'rows'     => $rows,
            'duration' => $duration,
            'output'   => implode("\n", $output),
        ];
    }
}
