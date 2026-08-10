<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Content Security Policy
        $response->headers->set('Content-Security-Policy', 
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
            "img-src 'self' data: https: http:; " .
            "font-src 'self' data: https://fonts.gstatic.com https://fonts.googleapis.com; " .
            "connect-src 'self' https://*.supabase.co; " .
            "frame-src 'self'; " .
            "object-src 'none'; " .
            "base-uri 'self';"
        );

        // X-Content-Type-Options
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-Frame-Options
        $response->headers->set('X-Frame-Options', 'DENY');

        // X-XSS-Protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Strict-Transport-Security
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Referrer-Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy
        $response->headers->set('Permissions-Policy', 
            'geolocation=(), ' .
            'microphone=(), ' .
            'camera=(), ' .
            'payment=()'
        );

        // X-Permitted-Cross-Domain-Policies
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // Remove server information
        $response->headers->remove('X-Powered-By');
        $response->headers->set('Server', '');

        return $response;
    }
}