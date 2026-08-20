<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheInvalidationService
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Invalidate cache when sale is created/updated/deleted
     */
    public function invalidateSaleCache(int $businessId): void
    {
        $this->cacheService->invalidateTags(['sales', "business_{$businessId}"]);
        $this->cacheService->invalidateTags(['dashboard', 'statistics']);
    }

    /**
     * Invalidate cache when purchase is created/updated/deleted
     */
    public function invalidatePurchaseCache(int $businessId): void
    {
        $this->cacheService->invalidateTags(['purchases', "business_{$businessId}"]);
        $this->cacheService->invalidateTags(['dashboard', 'statistics']);
    }

    /**
     * Invalidate cache when product is created/updated/deleted
     */
    public function invalidateProductCache(int $businessId, ?int $productId = null): void
    {
        $this->cacheService->invalidateTags(['products', "business_{$businessId}"]);
        $this->cacheService->invalidateTags(['inventory', 'dashboard']);
        
        if ($productId) {
            $this->cacheService->forget("product_{$productId}");
            $this->cacheService->forget("stock_{$productId}");
        }
    }

    /**
     * Invalidate cache when stock changes
     */
    public function invalidateStockCache(int $businessId, ?int $productId = null): void
    {
        $this->cacheService->invalidateTags(['stock', "business_{$businessId}"]);
        $this->cacheService->invalidateTags(['inventory', 'dashboard']);
        
        if ($productId) {
            $this->cacheService->forget("stock_{$productId}");
        }
    }

    /**
     * Invalidate cache when party (customer/supplier) changes
     */
    public function invalidatePartyCache(int $businessId): void
    {
        $this->cacheService->invalidateTags(['parties', "business_{$businessId}"]);
        $this->cacheService->invalidateTags(['customers', 'dashboard']);
    }

    /**
     * Invalidate cache when subscription changes
     */
    public function invalidateSubscriptionCache(int $businessId): void
    {
        $this->cacheService->invalidateTags(['subscriptions', "business_{$businessId}"]);
        $this->cacheService->invalidateTags(['dashboard', 'statistics']);
    }

    /**
     * Invalidate cache when settings change
     */
    public function invalidateSettingsCache(int $businessId): void
    {
        $this->cacheService->invalidateTags(['settings', "business_{$businessId}"]);
    }

    /**
     * Invalidate cache when user changes
     */
    public function invalidateUserCache(int $userId, ?int $businessId = null): void
    {
        $this->cacheService->invalidateTags(['users', "user_{$userId}"]);
        $this->cacheService->invalidateTags(['permissions', "user_{$userId}"]);
        
        if ($businessId) {
            $this->cacheService->invalidateTags(["business_{$businessId}"]);
        }
    }

    /**
     * Invalidate all caches for a business
     */
    public function invalidateAllBusinessCache(int $businessId): void
    {
        $this->cacheService->invalidateBusiness($businessId);
    }

    /**
     * Warm up cache for frequently accessed data
     */
    public function warmupCache(int $businessId): void
    {
        // Pre-cache business settings
        $this->cacheService->rememberForBusiness($businessId, 'settings', 3600, function () use ($businessId) {
            return \App\Models\Setting::where('business_id', $businessId)->first()->toArray() ?? [];
        });

        // Pre-cache user permissions
        $userId = auth()->id();
        if ($userId) {
            $this->cacheService->remember("user_permissions_{$userId}", 3600, function () use ($userId) {
                $user = \App\Models\User::find($userId);
                return $user ? $user->getAllPermissions()->pluck('name')->toArray() : [];
            });
        }
    }

    /**
     * Clear all caches (use with caution)
     */
    public function clearAllCaches(): void
    {
        $this->cacheService->clearAll();
    }
}
