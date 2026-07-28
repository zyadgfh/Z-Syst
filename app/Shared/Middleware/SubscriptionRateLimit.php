<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $key Key for rate limiting (e.g., 'api', 'pos')
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $key = 'api'): Response
    {
        $user = $request->user();
        $companyId = $user->company_id ?? app('tenant.company_id');

        // Get subscription plan for the company
        $subscription = Subscription::where('company_id', $companyId)->first();
        $plan = $subscription?->plan;

        // Define rate limits per plan (requests per minute)
        $rateLimits = [
            'free' => 100,
            'starter' => 1000,
            'professional' => 10000,
            'enterprise' => 100000,
        ];

        $planName = $plan?->name?->toLowerCase() ?? 'free';
        $maxAttempts = $rateLimits[$planName] ?? $rateLimits['free'];

        // Create a unique key for this user/company
        $rateKey = "subscription:{$key}:{$companyId}";

        // Check if rate limit exceeded
        if (RateLimiter::tooManyAttempts($rateKey, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($rateKey);

            return new JsonResponse([
                'success' => false,
                'message' => 'Rate limit exceeded',
                'retry_after' => $retryAfter,
                'limit' => $maxAttempts,
            ], 429);
        }

        // Increment the rate limit counter
        RateLimiter::hit($rateKey, 60); // 60 seconds

        $response = $next($request);

        // Add rate limit headers
        return $this->addRateLimitHeaders($response, $rateKey, $maxAttempts);
    }

    /**
     * Add rate limit headers to response.
     */
    private function addRateLimitHeaders(Response $response, string $rateKey, int $maxAttempts): Response
    {
        $remaining = $maxAttempts - RateLimiter::attempts($rateKey);
        $retryAfter = RateLimiter::availableIn($rateKey);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remaining),
            'X-RateLimit-Reset' => $retryAfter,
            'X-Subscription-Plan' => $response->headers->get('X-Subscription-Plan', 'free'),
        ]);
    }
}