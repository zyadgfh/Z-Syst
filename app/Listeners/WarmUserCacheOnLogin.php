<?php

namespace App\Listeners;

use App\Services\CacheWarmingService;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WarmUserCacheOnLogin implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 1;

    protected CacheWarmingService $cacheWarmingService;

    /**
     * Create the event listener.
     */
    public function __construct(CacheWarmingService $cacheWarmingService)
    {
        $this->cacheWarmingService = $cacheWarmingService;
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user) {
            // Warm up cache for the logged in user
            $this->cacheWarmingService->warmupForUser($user);
        }
    }
}
