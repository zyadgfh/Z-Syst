<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class ErrorLoggingService
{
    /**
     * Log application errors with context.
     *
     * @param Throwable $exception
     * @param array $context
     * @param string $level
     * @return void
     */
    public function logError(Throwable $exception, array $context = [], string $level = 'error'): void
    {
        $logData = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'user_id' => auth()->check() ? auth()->id() : null,
            'business_id' => auth()->check() ? auth()->user()->business_id ?? null : null,
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        Log::log($level, 'Application error occurred', $logData);
    }

    /**
     * Log database errors.
     *
     * @param Throwable $exception
     * @param string $query
     * @param array $bindings
     * @return void
     */
    public function logDatabaseError(Throwable $exception, string $query = '', array $bindings = []): void
    {
        $this->logError($exception, [
            'type' => 'database',
            'query' => $query,
            'bindings' => $bindings,
        ], 'error');
    }

    /**
     * Log validation errors.
     *
     * @param array $errors
     * @param array $data
     * @return void
     */
    public function logValidationError(array $errors, array $data = []): void
    {
        Log::warning('Validation failed', [
            'errors' => $errors,
            'data' => $data,
            'user_id' => auth()->check() ? auth()->id() : null,
            'business_id' => auth()->check() ? auth()->user()->business_id ?? null : null,
            'url' => request()->fullUrl(),
        ]);
    }

    /**
     * Log business logic errors.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logBusinessError(string $message, array $context = []): void
    {
        Log::warning('Business logic error', [
            'message' => $message,
            'context' => $context,
            'user_id' => auth()->check() ? auth()->id() : null,
            'business_id' => auth()->check() ? auth()->user()->business_id ?? null : null,
            'url' => request()->fullUrl(),
        ]);
    }

    /**
     * Log performance issues.
     *
     * @param string $operation
     * @param float $duration
     * @param array $context
     * @return void
     */
    public function logPerformance(string $operation, float $duration, array $context = []): void
    {
        if ($duration > 1.0) { // Log if operation takes more than 1 second
            Log::warning('Performance issue detected', [
                'operation' => $operation,
                'duration' => $duration,
                'context' => $context,
                'user_id' => auth()->check() ? auth()->id() : null,
                'business_id' => auth()->check() ? auth()->user()->business_id ?? null : null,
            ]);
        }
    }
}