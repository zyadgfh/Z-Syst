<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogApiRequests
{
    public function handle($request, Closure $next)
    {
        $requestId = (string) Str::uuid();
        $request->merge(['_request_id' => $requestId]);

        $startTime = microtime(true);

        Log::channel('api')->info('API Request', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'business_id' => auth()->user()?->business_id,
        ]);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        Log::channel('api')->info('API Response', [
            'request_id' => $requestId,
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration * 1000, 2),
        ]);

        // Alert on slow responses
        if ($duration > 2.0) {
            Log::channel('critical')->warning('Slow API Response', [
                'request_id' => $requestId,
                'url' => $request->fullUrl(),
                'duration_ms' => round($duration * 1000, 2),
            ]);
        }

        return $response;
    }
}
