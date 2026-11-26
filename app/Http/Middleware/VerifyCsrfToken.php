<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    // L-01: previously listed ssl-commerz/phonepe/paytm exemptions for routes
    // that do not exist in this codebase — removed (dead attack surface).
    // Stripe webhooks verify authenticity via `Stripe-Signature` HMAC headers
    // rather than browser session CSRF tokens.
    // Gateway callbacks (Fawry's Server Notification V2 POSTs here) are the
    // same case: server-to-server deliveries carrying an HMAC over the payload
    // (messageSignature covers orderStatus), which a gateway cannot present a
    // session token for. The callback route still verifies that signature
    // before the transaction is allowed to settle.
    protected $except = [
        'webhooks/stripe/*',
        'gateway/*/callback/*',
    ];

}
