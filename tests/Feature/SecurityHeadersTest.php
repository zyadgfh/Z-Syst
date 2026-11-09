<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_are_present_on_homepage()
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        // Keep in sync with App\Http\Middleware\SecurityHeaders. script-src
        // still needs 'unsafe-inline' (54 inline <script> blocks in the Blade
        // views) and 'unsafe-eval' (Alpine compiles x-data with new Function),
        // plus the two external origins the app actually loads: Chart.js from
        // cdn.jsdelivr.net and the login captcha from js.hcaptcha.com. The rest
        // of the policy stays locked down (object-src, base-uri,
        // frame-ancestors, form-action).
        $response->assertHeader('Content-Security-Policy',
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
    }
}
