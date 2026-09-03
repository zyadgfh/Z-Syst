<?php

namespace App\Listeners;

use App\Events\CacheInvalidationEvent;
use App\Services\CacheInvalidationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CacheInvalidationListener implements ShouldQueue
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

    protected CacheInvalidationService $cacheInvalidationService;

    /**
     * Create the event listener.
     */
    public function __construct(CacheInvalidationService $cacheInvalidationService)
    {
        $this->cacheInvalidationService = $cacheInvalidationService;
    }

    /**
     * Handle the event.
     */
    public function handle(CacheInvalidationEvent $event): void
    {
        $modelClass = $event->modelClass;
        $businessId = $event->businessId;

        if (!$businessId) {
            return;
        }

        // Get the short model name for cache tag
        $shortModelName = class_basename($modelClass);

        // Invalidate based on model type
        switch ($shortModelName) {
            case 'Sale':
                $this->cacheInvalidationService->invalidateSaleCache($businessId);
                break;

            case 'Purchase':
                $this->cacheInvalidationService->invalidatePurchaseCache($businessId);
                break;

            case 'Product':
            case 'Stock':
                $this->cacheInvalidationService->invalidateProductCache($businessId, $event->modelId);
                break;

            case 'Party':
                $this->cacheInvalidationService->invalidatePartyCache($businessId);
                break;

            case 'PlanSubscribe':
            case 'Subscription':
                $this->cacheInvalidationService->invalidateSubscriptionCache($businessId);
                break;

            case 'Setting':
            case 'SystemSetting':
                $this->cacheInvalidationService->invalidateSettingsCache($businessId);
                break;

            case 'User':
                $this->cacheInvalidationService->invalidateUserCache($event->modelId, $businessId);
                break;

            default:
                // For unknown models, invalidate all business cache
                $this->cacheInvalidationService->invalidateAllBusinessCache($businessId);
                break;
        }

        // Also invalidate dashboard cache
        $this->cacheInvalidationService->invalidateAllBusinessCache($businessId);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Cache invalidation failed: ' . $exception->getMessage());
    }
}
