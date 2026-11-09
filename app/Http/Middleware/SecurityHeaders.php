<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        // Hardened, but still compatible with what the Blade views actually ship:
        //   - 'unsafe-inline'/'unsafe-eval': 54 inline <script> blocks plus Alpine
        //     (x-data expressions compile via new Function, which CSP blocks
        //     without 'unsafe-eval'). Dropping them silently disables every
        //     script on the admin dashboard, POS, reports and login pages.
        //     Follow-up: switch to per-request nonces, then drop 'unsafe-inline'.
        //   - cdn.jsdelivr.net: Chart.js on admin/reports/product-analytics.
        //   - js.hcaptcha.com (+ frame-src for its challenge iframe): login captcha.
        // Everything that costs nothing stays locked down: object-src, base-uri,
        // frame-ancestors, form-action.
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; "
            ."script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://js.hcaptcha.com; "
            ."style-src 'self' 'unsafe-inline'; "
            ."img-src 'self' data: blob: https:; "
            ."font-src 'self' data:; "
            ."connect-src 'self' https: wss:; "
            ."frame-src 'self' https://js.hcaptcha.com https://hcaptcha.com https://newassets.hcaptcha.com; "
            ."object-src 'none'; "
            ."base-uri 'self'; "
            ."frame-ancestors 'none'; "
            ."form-action 'self';"
        );

        return $response;
    }
}
