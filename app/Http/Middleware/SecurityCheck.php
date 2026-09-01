<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;

class SecurityCheck
{
    protected SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function handle(Request $request, Closure $next)
    {
        // Check for SQL injection in query parameters
        foreach ($request->query() as $key => $value) {
            if (is_string($value) && $this->securityService->detectSqlInjection($value)) {
                return response()->json([
                    'error' => 'Invalid input detected',
                    'message' => 'Potentially malicious pattern found in request'
                ], 400);
            }
        }

        // Check for XSS in query parameters
        foreach ($request->query() as $key => $value) {
            if (is_string($value) && $this->securityService->detectXss($value)) {
                return response()->json([
                    'error' => 'Invalid input detected',
                    'message' => 'Potentially malicious pattern found in request'
                ], 400);
            }
        }

        // Check request body for similar patterns
        if ($request->isJson()) {
            $body = $request->json()->all();
            foreach ($body as $key => $value) {
                if (is_string($value)) {
                    if ($this->securityService->detectSqlInjection($value) || 
                        $this->securityService->detectXss($value) ||
                        $this->securityService->detectCommandInjection($value)) {
                        return response()->json([
                            'error' => 'Invalid input detected',
                            'message' => 'Potentially malicious pattern found in request body'
                        ], 400);
                    }
                }
            }
        }

        return $next($request);
    }
}