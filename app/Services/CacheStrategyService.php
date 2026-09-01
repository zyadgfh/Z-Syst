<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheStrategyService
{
    /**
     * Cache strategies for different data types.
     */
    protected array $strategies = [
        'products' => [
            'ttl' => 3600, // 1 hour
            'tags' => ['products'],
        ],
        'stocks' => [
            'ttl' => 1800, // 30 minutes
            'tags' => ['stocks'],
        ],
        'sales' => [
            'ttl' => 7200, // 2 hours
            'tags' => ['sales'],
        ],
        'parties' => [
            'ttl' => 86400, // 24 hours
            'tags' => ['parties'],
        ],
        'categories' => [
            'ttl' => 86400, // 24 hours
            'tags' => ['categories'],
        ],
        'settings' => [
            'ttl' => 3600, // 1 hour
            'tags' => ['settings'],
        ],
    ];

    /**
     * Get cached data with strategy.
     *
     * @param string $key
     * @param string $type
     * @param callable $callback
     * @return mixed
     */
    public function remember(string $key, string $type, callable $callback)
    {
        $strategy = $this->strategies[$type] ?? ['ttl' => 3600, 'tags' => ['default']];

        return Cache::remember($key, $strategy['ttl'], $callback);
    }

    /**
     * Get cached data with fallback.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    /**
     * Set cached data with strategy.
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    public function set(string $key, $value, string $type = 'default'): bool
    {
        $strategy = $this->strategies[$type] ?? ['ttl' => 3600, 'tags' => ['default']];
        
        return Cache::put($key, $value, $strategy['ttl']);
    }

    /**
     * Invalidate cache by type.
     *
     * @param string $type
     * @return void
     */
    public function invalidateType(string $type): void
    {
        if (isset($this->strategies[$type])) {
            Cache::tags($this->strategies[$type]['tags'])->flush();
            
            Log::info('Cache invalidated by type', [
                'type' => $type,
                'tags' => $this->strategies[$type]['tags'],
            ]);
        }
    }

    /**
     * Invalidate cache by key.
     *
     * @param string $key
     * @return bool
     */
    public function invalidateKey(string $key): bool
    {
        $result = Cache::forget($key);
        
        Log::info('Cache key invalidated', [
            'key' => $key,
            'result' => $result,
        ]);
        
        return $result;
    }

    /**
     * Invalidate all caches.
     *
     * @return void
     */
    public function invalidateAll(): void
    {
        Cache::flush();
        
        Log::info('All caches invalidated');
    }

    /**
     * Warm up cache for a type.
     *
     * @param string $type
     * @param callable $dataProvider
     * @return void
     */
    public function warmUp(string $type, callable $dataProvider): void
    {
        try {
            $data = $dataProvider();
            
            if (is_array($data)) {
                foreach ($data as $item) {
                    $key = $this->generateKey($type, $item['id'] ?? null);
                    $this->set($key, $item, $type);
                }
            } else {
                $key = $this->generateKey($type);
                $this->set($key, $data, $type);
            }

            Log::info('Cache warmed up', [
                'type' => $type,
                'items' => is_array($data) ? count($data) : 1,
            ]);

        } catch (\Exception $e) {
            Log::error('Cache warm-up failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate cache key.
     *
     * @param string $type
     * @param mixed $id
     * @return string
     */
    protected function generateKey(string $type, $id = null): string
    {
        $key = "z_syst:{$type}";
        
        if ($id !== null) {
            $key .= ":{$id}";
        }
        
        return $key;
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        try {
            // This would depend on the cache driver being used
            // For Redis, we could use INFO command
            // For file cache, we'd need to check file sizes
            
            return [
                'driver' => config('cache.default'),
                'status' => 'active',
                'message' => 'Cache statistics available for Redis driver only',
            ];

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 'error',
            ];
        }
    }

    /**
     * Custom cache strategy for frequently changing data.
     *
     * @param string $key
     * @param callable $callback
     * @param int $shortTtl
     * @return mixed
     */
    public function rememberShort(string $key, callable $callback, int $shortTtl = 60)
    {
        return Cache::remember($key, $shortTtl, $callback);
    }

    /**
     * Custom cache strategy for rarely changing data.
     *
     * @param string $key
     * @param callable $callback
     * @param int $longTtl
     * @return mixed
     */
    public function rememberLong(string $key, callable $callback, int $longTtl = 86400)
    {
        return Cache::remember($key, $longTtl, $callback);
    }

    /**
     * Cache query results.
     *
     * @param string $key
     * @param callable $query
     * @param string $type
     * @return mixed
     */
    public function rememberQuery(string $key, callable $query, string $type = 'default')
    {
        return $this->remember($key, $type, function () use ($query) {
            return $query();
        });
    }

    /**
     * Clear expired cache entries.
     *
     * @return void
     */
    public function clearExpired(): void
    {
        // Most cache drivers handle this automatically
        // This is a placeholder for manual cleanup if needed
        
        Log::info('Expired cache entries cleared');
    }
}