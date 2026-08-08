<?php

namespace App\Helpers;

use App\Exceptions\RenderableException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class Logger
{
    public static function business(string $operation, string $status, array $data = []): void
    {
        Log::channel('business')->log(
            $status === 'failed' ? 'warning' : 'info',
            "Business operation: {$operation}",
            array_merge([
                'operation' => $operation,
                'status' => $status,
                'user_id' => auth()->id(),
                'business_id' => auth()->user()?->business_id,
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
            'user_id' => auth()->id(),
            'business_id' => auth()->user()?->business_id,
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
