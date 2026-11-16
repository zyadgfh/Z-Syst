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
            $exitCode = Artisan::call('backup:database', ['--disk' => 'local']);
            if ($exitCode !== 0) {
                throw new \RuntimeException('Database backup command failed');
            }
            $output = trim(Artisan::output());

            AuditLogger::log('backup.created', 'Database backup created by administrator.', [
                'output' => $output,
            ]);

            return response()->json([
                'message' => __('Backup created successfully.'),
                'data' => ['output' => $output],
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
