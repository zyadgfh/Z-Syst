<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Enhanced Cache Service with specialized tags and methods
 */
class CacheService
{
    // TTL Constants
    public const TTL_SHORT = 60;           // 1 minute
    public const TTL_MEDIUM = 300;         // 5 minutes
    public const TTL_LONG = 1800;          // 30 minutes
    public const TTL_VERY_LONG = 3600;     // 1 hour
    public const TTL_DAY = 86400;          // 1 day
    /**
     * Remember a value in cache with TTL
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Cache with tags for easy invalidation
     */
    public function rememberWithTags(string $key, array $tags, $ttl, callable $callback)
    {
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    /**
     * Remember forever with tags
     */
    public function rememberForeverWithTags(string $key, array $tags, callable $callback)
    {
        return Cache::tags($tags)->remember($key, now()->addDays(30), $callback);
    }

    /**
     * Get or set cache with business context
     */
    public function rememberForBusiness(int $businessId, string $key, int $ttl, callable $callback)
    {
        $cacheKey = "business:{$businessId}:{$key}";
        return Cache::tags(["business_{$businessId}"])->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Invalidate cache for specific business
     */
    public function invalidateBusiness(int $businessId): void
    {
        Cache::tags(["business_{$businessId}"])->flush();
    }

    /**
     * Invalidate specific cache key
     */
    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Increment cache counter
     */
    public function increment(string $key, int $value = 1): int
    {
        return Cache::increment($key, $value);
    }

    /**
     * Decrement cache counter
     */
    public function decrement(string $key, int $value = 1): int
    {
        return Cache::decrement($key, $value);
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Put value in cache
     */
    public function put(string $key, $value, int $ttl = 3600): void
    {
        Cache::put($key, $value, $ttl);
    }

    /**
     * Get value from cache
     */
    public function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
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

    // ===== Specialized Cache Methods =====

    /**
     * Remember with automatic TTL based on data type
     */
    public function rememberWithType(string $key, string $dataType, callable $callback)
    {
        $ttl = CacheTags::getTTL($dataType);
        $tags = CacheTags::getInvalidationTags($dataType);
        
        return $this->rememberWithTags($key, $tags, $ttl, $callback);
    }

    /**
     * Get invalidation tags for model class
     */
    protected function getInvalidationTags(string $dataType): array
    {
        return match($dataType) {
            'sales' => ['sales', 'sales:list', 'dashboard'],
            'purchases' => ['purchases', 'purchases:list', 'dashboard'],
            'products' => ['products', 'products:list', 'stock'],
            'stock' => ['stock', 'stock:list', 'products'],
            'customers' => ['customers', 'parties'],
            'suppliers' => ['suppliers', 'parties'],
            default => [$dataType],
        };
    }

    /**
     * Cache with multiple tags for fine-grained invalidation
     */
    public function rememberWithMultipleTags(string $key, array $tags, int $ttl, callable $callback)
    {
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    /**
     * Invalidate by model class
     */
    public function invalidateByModel(string $modelClass, ?int $businessId = null): void
    {
        $tags = CacheTags::getModelInvalidationTags($modelClass, $businessId);
        $this->invalidateTags($tags);
    }

    /**
     * Invalidate by business and data type
     */
    public function invalidateBusinessData(int $businessId, string $dataType): void
    {
        $tags = CacheTags::getBusinessDataTags($businessId, $dataType);
        $this->invalidateTags($tags);
    }

    /**
     * Remember for API endpoint
     */
    public function rememberForApi(string $endpoint, int $businessId, int $ttl, callable $callback)
    {
        $key = "api:{$endpoint}:{$businessId}";
        $tags = ['api', "api:{$endpoint}", "business:{$businessId}"];
        
        return $this->rememberWithTags($key, $tags, $ttl, $callback);
    }

    /**
     * Invalidate API cache
     */
    public function invalidateApiCache(?string $endpoint = null, ?int $businessId = null): void
    {
        $tags = ['api'];
        
        if ($endpoint) {
            $tags[] = "api:{$endpoint}";
        }
        
        if ($businessId) {
            $tags[] = "business:{$businessId}";
        }
        
        $this->invalidateTags($tags);
    }
}