<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscriptionLimits
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function handle(Request $request, Closure $next, string $metricName)
    {
        $user = Auth::user();
        if (!$user || !$user->business_id) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated or no business associated',
            ], 401);
        }

        $withinLimits = $this->subscriptionService->checkLimits($user->business_id, $metricName);

        if (!$withinLimits) {
            return response()->json([
                'success' => false,
                'message' => "You have reached your {$metricName} limit. Please upgrade your subscription.",
                'metric_name' => $metricName,
            ], 403);
        }

        return $next($request);
    }
}
