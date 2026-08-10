<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * Cache with tags for easy invalidation
     */
    public function rememberWithTags(string $key, array $tags, $ttl, callable $callback)
    {
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateTags(array $tags): void
    {
        Cache::tags($tags)->flush();
    }

    /**
     * Cache frequently accessed data
     */
    public function cacheProduct(int $productId, array $data, int $ttl = 3600): void
    {
        Cache::tags(['products', "product_{$productId}"])
            ->put("product_{$productId}", $data, $ttl);
    }

    /**
     * Get cached product
     */
    public function getCachedProduct(int $productId): ?array
    {
        return Cache::tags(['products', "product_{$productId}"])
            ->get("product_{$productId}");
    }

    /**
     * Cache stock data
     */
    public function cacheStockData(int $productId, array $data, int $ttl = 300): void
    {
        Cache::tags(['stock', "stock_{$productId}"])
            ->put("stock_{$productId}", $data, $ttl);
    }

    /**
     * Get cached stock data
     */
    public function getCachedStockData(int $productId): ?array
    {
        return Cache::tags(['stock', "stock_{$productId}"])
            ->get("stock_{$productId}");
    }

    /**
     * Cache user permissions
     */
    public function cacheUserPermissions(int $userId, array $permissions, int $ttl = 3600): void
    {
        Cache::tags(['permissions', "user_{$userId}"])
            ->put("user_permissions_{$userId}", $permissions, $ttl);
    }

    /**
     * Get cached user permissions
     */
    public function getCachedUserPermissions(int $userId): ?array
    {
        return Cache::tags(['permissions', "user_{$userId}"])
            ->get("user_permissions_{$userId}");
    }

    /**
     * Cache business settings
     */
    public function cacheBusinessSettings(int $businessId, array $settings, int $ttl = 7200): void
    {
        Cache::tags(['settings', "business_{$businessId}"])
            ->put("business_settings_{$businessId}", $settings, $ttl);
    }

    /**
     * Get cached business settings
     */
    public function getCachedBusinessSettings(int $businessId): ?array
    {
        return Cache::tags(['settings', "business_{$businessId}"])
            ->get("business_settings_{$businessId}");
    }

    /**
     * Clear all cache
     */
    public function clearAll(): void
    {
        Cache::flush();
    }

    /**
     * Clear specific tag cache
     */
    public function clearTag(string $tag): void
    {
        Cache::tags([$tag])->flush();
    }

    /**
     * Warm up cache for frequently accessed data
     */
    public function warmupCache(array $data): void
    {
        foreach ($data as $key => $value) {
            Cache::put($key, $value, now()->addHours(6));
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            return [
                'driver' => 'redis',
                'stats' => Redis::info('stats'),
            ];
        }

        return [
            'driver' => config('cache.default'),
            'stats' => 'Not available for this driver',
        ];
    }
}