<?php

namespace App\Http;

use App\Http\Middleware\EnforceBranchLimit;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;

class Kernel extends HttpKernel
{
    protected $middleware = [
        // Global middleware trimmed — `EnforceBranchLimit` moved to route middleware
    ];

    protected $middlewareGroups = [
        'web' => [
            // Web middleware group
        ],

        'api' => [
            SubstituteBindings::class,
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
        ],
    ];

    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'can' => Authorize::class,
        'throttle' => ThrottleRequests::class,
        'bindings' => SubstituteBindings::class,
        'enforce.branch' => EnforceBranchLimit::class,
    ];
}
