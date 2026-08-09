<?php

namespace App\Http\Middleware;

use App\Services\CSRFProtectionService;
use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityCheck
{
    protected SecurityService $securityService;

    protected CSRFProtectionService $csrfService;

    public function __construct(
        SecurityService $securityService,
        CSRFProtectionService $csrfService
    ) {
        $this->securityService = $securityService;
        $this->csrfService = $csrfService;
    }

    public function handle(Request $request, Closure $next)
    {
        // Check for SQL injection patterns in input
        $this->checkForSQLInjection($request);

        // Check for XSS patterns in input
        $this->checkForXSS($request);

        // Validate CSRF token for POST requests
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')) {
            $this->validateCSRF($request);
        }

        return $next($request);
    }

    /**
     * Check for SQL injection patterns
     */
    protected function checkForSQLInjection(Request $request): void
    {
        $allInputs = $request->all();

        foreach ($allInputs as $key => $value) {
            if (is_string($value)) {
                $sanitized = $this->securityService->sanitizeInput($value);
                if ($sanitized !== $value) {
                    Log::warning('Potential SQL injection detected', [
                        'input_key' => $key,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    // Optionally block the request
                    // abort(403, 'Security violation detected');
                }
            }
        }
    }

    /**
     * Check for XSS patterns
     */
    protected function checkForXSS(Request $request): void
    {
        $allInputs = $request->all();

        foreach ($allInputs as $key => $value) {
            if (is_string($value)) {
                if ($this->securityService->checkXSS($value)) {
                    Log::warning('Potential XSS attack detected', [
                        'input_key' => $key,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    // Sanitize the input
                    $request->merge([$key => $this->securityService->sanitizeHTML($value)]);
                }
            }
        }
    }

    /**
     * Validate CSRF token
     */
    protected function validateCSRF(Request $request): void
    {
        // Skip CSRF validation for API routes
        if ($request->expectsJson()) {
            return;
        }

        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (! $token || ! $this->csrfService->verifyToken($token)) {
            Log::warning('CSRF token validation failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(419, 'CSRF token mismatch');
        }
    }
}
