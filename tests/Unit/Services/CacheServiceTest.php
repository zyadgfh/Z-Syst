<?php

namespace Tests\Unit\Services;

use App\Services\CacheService;
use App\Services\CacheTags;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    protected CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(CacheService::class);
    }

    public function test_remember_caches_value(): void
    {
        $key = 'test_key';
        $value = ['data' => 'test'];
        
        $result = $this->cacheService->remember($key, 60, function () use ($value) {
            return $value;
        });
        
        $this->assertEquals($value, $result);
        $this->assertEquals($value, Cache::get($key));
    }

    public function test_remember_returns_cached_value_on_subsequent_calls(): void
    {
        $key = 'test_key_2';
        $callCount = 0;
        
        $result1 = $this->cacheService->remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return 'value_' . $callCount;
        });
        
        $result2 = $this->cacheService->remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return 'value_' . $callCount;
        });
        
        $this->assertEquals($result1, $result2);
        $this->assertEquals(1, $callCount); // Callback should only be called once
    }

    public function test_forget_removes_cache(): void
    {
        $key = 'test_forget';
        Cache::put($key, 'value', 60);
        
        $this->cacheService->forget($key);
        
        $this->assertNull(Cache::get($key));
    }

    public function test_has_checks_cache_existence(): void
    {
        $key = 'test_has';
        
        $this->assertFalse($this->cacheService->has($key));
        
        Cache::put($key, 'value', 60);
        
        $this->assertTrue($this->cacheService->has($key));
    }

    public function test_increment_increases_value(): void
    {
        $key = 'test_increment';
        Cache::put($key, 5, 60);
        
        $result = $this->cacheService->increment($key, 3);
        
        $this->assertEquals(8, $result);
    }

    public function test_decrement_decreases_value(): void
    {
        $key = 'test_decrement';
        Cache::put($key, 10, 60);
        
        $result = $this->cacheService->decrement($key, 4);
        
        $this->assertEquals(6, $result);
    }

    public function test_business_key_generation(): void
    {
        $key = CacheTags::businessKey(123, 'products');
        
        $this->assertEquals('business:123:products', $key);
    }

    public function test_ttl_values_are_valid(): void
    {
        $this->assertEquals(300, CacheTags::getTTL(CacheTags::DASHBOARD));
        $this->assertEquals(300, CacheTags::getTTL(CacheTags::SALES));
        $this->assertEquals(1800, CacheTags::getTTL(CacheTags::PRODUCTS));
        $this->assertEquals(3600, CacheTags::getTTL(CacheTags::SETTINGS));
    }
}
