<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MonitoringService
{
    /**
     * Log performance metrics
     */
    public function logPerformance(string $action, float $duration, array $context = []): void
    {
        Log::info("Performance: {$action}", [
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log error with context
     */
    public function logError(string $message, \Throwable $exception, array $context = []): void
    {
        Log::error($message, [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Track API response time
     */
    public function trackApiResponse(string $endpoint, int $statusCode, float $duration): void
    {
        $key = "api_stats:{$endpoint}";
        $stats = Cache::get($key, [
            'total_requests' => 0,
            'total_duration' => 0,
            'errors' => 0,
        ]);

        $stats['total_requests']++;
        $stats['total_duration'] += $duration;
        
        if ($statusCode >= 400) {
            $stats['errors']++;
        }

        Cache::put($key, $stats, now()->addHours(1));

        // Log slow requests
        if ($duration > 1.0) {
            Log::warning("Slow API request: {$endpoint}", [
                'duration' => round($duration * 1000, 2) . 'ms',
                'status_code' => $statusCode,
            ]);
        }
    }

    /**
     * Get API statistics
     */
    public function getApiStats(string $endpoint): array
    {
        $key = "api_stats:{$endpoint}";
        $stats = Cache::get($key, [
            'total_requests' => 0,
            'total_duration' => 0,
            'errors' => 0,
        ]);

        if ($stats['total_requests'] > 0) {
            $stats['avg_duration'] = $stats['total_duration'] / $stats['total_requests'];
            $stats['error_rate'] = ($stats['errors'] / $stats['total_requests']) * 100;
        } else {
            $stats['avg_duration'] = 0;
            $stats['error_rate'] = 0;
        }

        return $stats;
    }

    /**
     * Log database query performance
     */
    public function logQueryPerformance(string $query, float $duration): void
    {
        if ($duration > 0.1) { // Log queries taking more than 100ms
            Log::warning("Slow database query", [
                'query' => substr($query, 0, 200), // First 200 chars
                'duration_ms' => round($duration * 1000, 2),
            ]);
        }
    }

    /**
     * Track user activity
     */
    public function trackUserActivity(int $userId, string $action, array $metadata = []): void
    {
        Log::info("User activity", [
            'user_id' => $userId,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
            ...$metadata,
        ]);
    }

    /**
     * Log system health check
     */
    public function healthCheck(): array
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => [],
        ];

        // Check database connection
        try {
            \DB::connection()->getPdo();
            $health['checks']['database'] = 'ok';
        } catch (\Exception $e) {
            $health['checks']['database'] = 'failed';
            $health['status'] = 'unhealthy';
        }

        // Check cache
        try {
            Cache::put('health_check', 'ok', 60);
            $health['checks']['cache'] = 'ok';
        } catch (\Exception $e) {
            $health['checks']['cache'] = 'failed';
            $health['status'] = 'unhealthy';
        }

        // Check disk space
        $freeSpace = disk_free_space(storage_path());
        $health['checks']['disk_space'] = $freeSpace ? 'ok' : 'failed';
        $health['disk_space_bytes'] = $freeSpace;

        return $health;
    }

    /**
     * Log custom metric
     */
    public function logMetric(string $name, float $value, array $tags = []): void
    {
        Log::info("Metric: {$name}", [
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}