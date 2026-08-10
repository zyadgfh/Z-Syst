<?php

namespace App\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StructuredLogger
{
    /**
     * Log payment processing event
     */
    public static function logPayment(array $data): void
    {
        Log::info('Payment processed', [
            'event' => 'payment_processed',
            'business_id' => $data['business_id'] ?? null,
            'amount' => $data['amount'] ?? null,
            'gateway' => $data['gateway'] ?? null,
            'status' => $data['status'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'trace_id' => self::getTraceId(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log Supabase operation error
     */
    public static function logSupabaseError(string $operation, array $context, \Throwable $exception): void
    {
        Log::error('Supabase operation failed', [
            'event' => 'supabase_error',
            'operation' => $operation,
            'context' => self::sanitizeContext($context),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'trace_id' => self::getTraceId(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log user action
     */
    public static function logUserAction(int $userId, string $action, array $details = []): void
    {
        Log::info('User action performed', [
            'event' => 'user_action',
            'user_id' => $userId,
            'action' => $action,
            'details' => self::sanitizeContext($details),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'trace_id' => self::getTraceId(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log API request
     */
    public static function logApiRequest(string $endpoint, string $method, array $params = []): void
    {
        Log::info('API request received', [
            'event' => 'api_request',
            'endpoint' => $endpoint,
            'method' => $method,
            'params' => self::sanitizeContext($params),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'trace_id' => self::getTraceId(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log API response
     */
    public static function logApiResponse(string $endpoint, int $statusCode, float $duration): void
    {
        Log::info('API response sent', [
            'event' => 'api_response',
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
            'trace_id' => self::getTraceId(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log database query
     */
    public static function logDatabaseQuery(string $query, float $duration): void
    {
        Log::debug('Database query executed', [
            'event' => 'database_query',
            'query' => self::sanitizeQuery($query),
            'duration_ms' => round($duration * 1000, 2),
            'trace_id' => self::getTraceId(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log cache operation
     */
    public static function logCacheOperation(string $operation, string $key, bool $hit = null): void
    {
        Log::debug('Cache operation', [
            'event' => 'cache_operation',
            'operation' => $operation,
            'key' => $key,
            'hit' => $hit,
            'trace_id' => self::getTraceId(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log security event
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        Log::warning('Security event', [
            'event' => 'security_event',
            'security_event_type' => $event,
            'context' => self::sanitizeContext($context),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'trace_id' => self::getTraceId(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log business event
     */
    public static function logBusinessEvent(int $businessId, string $event, array $details = []): void
    {
        Log::info('Business event', [
            'event' => 'business_event',
            'business_id' => $businessId,
            'business_event_type' => $event,
            'details' => self::sanitizeContext($details),
            'trace_id' => self::getTraceId(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get current trace ID
     */
    protected static function getTraceId(): string
    {
        return request()->header('X-Trace-ID') ?? (string) Str::uuid();
    }

    /**
     * Sanitize context for logging
     */
    protected static function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'credit_card', 'ssn'];
        
        foreach ($context as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $context[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $context[$key] = self::sanitizeContext($value);
            }
        }
        
        return $context;
    }

    /**
     * Sanitize SQL query for logging
     */
    protected static function sanitizeQuery(string $query): string
    {
        // Remove sensitive data from SQL queries
        $query = preg_replace('/password\s*=\s*[\'"][^\'"]+[\'"]/', 'password = ***REDACTED***', $query);
        $query = preg_replace('/token\s*=\s*[\'"][^\'"]+[\'"]/', 'token = ***REDACTED***', $query);
        
        return $query;
    }
}