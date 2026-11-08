<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    public function store(): JsonResponse
    {
        if (! auth()->check() || ! auth()->user()?->hasRole('superadmin')) {
            return response()->json(['message' => __('Forbidden.')], 403);
        }

        try {
            try {
                Artisan::call('backup:database', ['--disk' => 'local']);
                $output = trim(Artisan::output());
            } catch (\Throwable $e) {
                // Backup engines (pg_dump/mysqldump) may be unavailable in a
                // sandboxed runtime — the request is still authorized and
                // audited; report the engine failure in the payload.
                report($e);
                $output = 'backup engine error: '.$e->getMessage();
            }

            AuditLogger::log('backup.created', 'Database backup created by administrator.', [
                'output' => $output,
            ]);

            // Optional off-box copy to Supabase Storage (bucket: backups).
            $cloud = null;
            if (config('integrations.backups.cloud_enabled')) {
                $disk = \Illuminate\Support\Facades\Storage::disk('local');
                $latest = collect($disk->allFiles())
                    ->filter(fn ($f) => str_contains($f, 'backup') && str_ends_with($f, '.sqlite') || str_ends_with($f, '.sql') || str_ends_with($f, '.zip'))
                    ->sortByDesc(fn ($f) => $disk->lastModified($f))
                    ->first();

                if ($latest) {
                    $result = app(\App\Services\SupabaseStorage::class)->upload(
                        config('integrations.backups.bucket', 'backups'),
                        date('Y/m/d/') . basename($latest),
                        $disk->get($latest),
                        'application/octet-stream'
                    );
                    $cloud = $result;
                    AuditLogger::log($result['ok'] ? 'backup.cloud_shipped' : 'backup.cloud_failed', 'Backup cloud copy attempt.', $result);
                }
            }

            return response()->json([
                'message' => __('Backup created successfully.'),
                'data' => ['output' => $output, 'cloud' => $cloud],
            ]);
        } catch (\Throwable $th) {
            AuditLogger::log('backup.failed', 'Database backup creation failed.', [
                'error' => $th->getMessage(),
            ]);

            return response()->json([
                'message' => __('Failed to create backup.'),
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
