<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends BaseController
{
    /**
     * Basic health check.
     */
    public function __invoke(): JsonResponse
    {
        return $this->success([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
        ], 'Health check passed');
    }

    /**
     * Readiness check - Database and Redis.
     */
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'cache' => $this->checkCache(),
        ];

        $healthy = collect($checks)->every(fn ($check) => $check['status'] === 'ok');

        if (! $healthy) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'checks' => $checks,
            ], 503);
        }

        return $this->success([
            'status' => 'ready',
            'checks' => $checks,
        ], 'Readiness check passed');
    }

    /**
     * Liveness check - Application is running.
     */
    public function live(): JsonResponse
    {
        return $this->success([
            'status' => 'alive',
            'timestamp' => now()->toISOString(),
        ], 'Liveness check passed');
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok', 'message' => 'Database connected'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            if (Redis::ping() === 'PONG' || Redis::ping() === true) {
                return ['status' => 'ok', 'message' => 'Redis connected'];
            }

            return ['status' => 'error', 'message' => 'Redis not responding'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check_test', 'ok', 10);
            $value = Cache::get('health_check_test');

            if ($value === 'ok') {
                return ['status' => 'ok', 'message' => 'Cache working'];
            }

            return ['status' => 'error', 'message' => 'Cache not working'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}