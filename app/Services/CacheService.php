<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheService
{
    /**
     * Cache configuration
     */
    protected array $config = [
        'default_ttl' => 3600, // 1 hour
        'short_ttl' => 300, // 5 minutes
        'long_ttl' => 86400, // 24 hours
        'business_prefix' => 'business',
        'user_prefix' => 'user',
        'product_prefix' => 'product',
    ];

    /**
     * Get cached data or execute callback
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Get cached data or execute callback forever
     */
    public function rememberForever(string $key, callable $callback)
    {
        return Cache::rememberForever($key, $callback);
    }

    /**
     * Forget cached data
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Clear all cache
     */
    public function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * Generate cache key for business data
     */
    public function businessKey(int $businessId, string $suffix): string
    {
        return "{$this->config['business_prefix']}:{$businessId}:{$suffix}";
    }

    /**
     * Generate cache key for user data
     */
    public function userKey(int $userId, string $suffix): string
    {
        return "{$this->config['user_prefix']}:{$userId}:{$suffix}";
    }

    /**
     * Generate cache key for product data
     */
    public function productKey(int $productId, string $suffix): string
    {
        return "{$this->config['product_prefix']}:{$productId}:{$suffix}";
    }

    /**
     * Cache business statistics
     */
    public function cacheBusinessStatistics(int $businessId, array $statistics): void
    {
        $key = $this->businessKey($businessId, 'statistics');
        Cache::put($key, $statistics, $this->config['short_ttl']);
    }

    /**
     * Get cached business statistics
     */
    public function getBusinessStatistics(int $businessId): ?array
    {
        $key = $this->businessKey($businessId, 'statistics');
        return Cache::get($key);
    }

    /**
     * Cache user permissions
     */
    public function cacheUserPermissions(int $userId, array $permissions): void
    {
        $key = $this->userKey($userId, 'permissions');
        Cache::put($key, $permissions, $this->config['default_ttl']);
    }

    /**
     * Get cached user permissions
     */
    public function getUserPermissions(int $userId): ?array
    {
        $key = $this->userKey($userId, 'permissions');
        return Cache::get($key);
    }

    /**
     * Cache product data
     */
    public function cacheProduct(int $productId, array $productData): void
    {
        $key = $this->productKey($productId, 'data');
        Cache::put($key, $productData, $this->config['default_ttl']);
    }

    /**
     * Get cached product data
     */
    public function getProduct(int $productId): ?array
    {
        $key = $this->productKey($productId, 'data');
        return Cache::get($key);
    }

    /**
     * Invalidate business cache
     */
    public function invalidateBusinessCache(int $businessId): void
    {
        $pattern = "{$this->config['business_prefix']}:{$businessId}:*";
        $this->invalidatePattern($pattern);
    }

    /**
     * Invalidate user cache
     */
    public function invalidateUserCache(int $userId): void
    {
        $pattern = "{$this->config['user_prefix']}:{$userId}:*";
        $this->invalidatePattern($pattern);
    }

    /**
     * Invalidate product cache
     */
    public function invalidateProductCache(int $productId): void
    {
        $pattern = "{$this->config['product_prefix']}:{$productId}:*";
        $this->invalidatePattern($pattern);
    }

    /**
     * Invalidate cache by pattern
     */
    protected function invalidatePattern(string $pattern): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->connection();
            $cursor = 0;

            do {
                $result = $redis->scan($cursor, ['match' => $pattern, 'count' => 100]);
                $cursor = is_array($result) ? (int) ($result[0] ?? 0) : 0;
                $keys = is_array($result) ? ($result[1] ?? []) : [];

                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } while ($cursor !== 0);
        }
    }

    /**
     * Cache query results
     */
    public function cacheQuery(string $queryKey, callable $query, int $ttl = null)
    {
        $ttl = $ttl ?? $this->config['default_ttl'];
        return Cache::remember("query:{$queryKey}", $ttl, $query);
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $stats = [
            'driver' => Cache::getDefaultDriver(),
            'enabled' => true,
        ];

        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->connection();
            $stats['redis'] = [
                'info' => $redis->info(),
                'keys_count' => count($redis->keys('*')),
            ];
        }

        return $stats;
    }

    /**
     * Warm up cache for business
     */
    public function warmupBusinessCache(int $businessId): void
    {
        // Cache business data
        $this->cacheBusinessStatistics($businessId, [
            'total_products' => DB::table('products')->where('business_id', $businessId)->count(),
            'total_sales' => DB::table('sales')->where('business_id', $businessId)->count(),
            'total_customers' => DB::table('parties')->where('business_id', $businessId)->where('type', 'customer')->count(),
        ]);
    }

    /**
     * Clear expired cache entries
     */
    public function clearExpired(): int
    {
        // Redis handles TTL automatically
        // For file cache, we could implement cleanup
        return 0;
    }

    /**
     * Check if cache is enabled
     */
    public function isEnabled(): bool
    {
        return config('cache.default') !== 'array';
    }

    /**
     * Get cache key prefix
     */
    public function getPrefix(): string
    {
        return Cache::getStore()->getPrefix();
    }
}
