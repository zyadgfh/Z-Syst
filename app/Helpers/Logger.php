<?php

namespace App\Helpers;

use App\Exceptions\RenderableException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class Logger
{
    /**
     * Auth context that can never break logging: during early boot (or when
     * the auth system itself is misconfigured) auth() throws, and since the
     * exception handler logs through this class, that would mask the real
     * error. Everything auth-related degrades to null here.
     */
    private static function authContext(): array
    {
        try {
            if (! app()->bound('auth') || ! app()->isBooted()) {
                return [];
            }

            $user = auth()->user();

            if (! $user) {
                return [];
            }

            return [
                'user_id' => $user->getAuthIdentifier(),
                'role' => $user->role ?? null,
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    public static function business(string $operation, string $status, array $data = []): void
    {
        Log::channel('business')->log(
            $status === 'failed' ? 'warning' : 'info',
            "Business operation: {$operation}",
            array_merge([
                'operation' => $operation,
                'status' => $status,
                ...self::authContext(),
                'timestamp' => now()->toIso8601String(),
            ], $data)
        );
    }

    public static function error(\Throwable $e, array $context = []): void
    {
        $logData = array_merge([
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'message' => $e->getMessage(),
            ...self::authContext(),
            'url' => request()?->fullUrl(),
            'method' => request()?->method(),
        ], $context);

        $level = 'error';

        if ($e instanceof RenderableException) {
            $level = $e->errorCode->logLevel();
        }

        if ($e instanceof QueryException) {
            $logData['sql'] = $e->getSql();
            $logData['bindings'] = $e->getBindings();
            $level = 'critical';
        }

        Log::channel('errors')->log($level, $e->getMessage(), $logData);

        if ($level === 'critical') {
            Log::channel('critical')->critical($e->getMessage(), $logData);
        }
    }
}
