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
    protected $except = [
        //
    ];

}
