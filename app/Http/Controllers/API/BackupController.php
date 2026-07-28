<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    public function store(): JsonResponse
    {
        try {
            Artisan::call('backup:database', ['--disk' => 'local']);
            $output = trim(Artisan::output());

            return response()->json([
                'message' => __('Backup created successfully.'),
                'data' => ['output' => $output],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => __('Failed to create backup.'),
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
