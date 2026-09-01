<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheWarmingService
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Warm up cache for application startup
     */
    public function warmupApplication(): void
    {
        try {
            $this->warmupPlans();
            $this->warmupCategories();
            $this->warmupSystemSettings();
            
            Log::info('Application cache warmed up successfully');
        } catch (\Exception $e) {
            Log::error('Cache warming failed: ' . $e->getMessage());
        }
    }

    /**
     * Warm up plans cache
     */
    public function warmupPlans(): void
    {
        $this->cacheService->remember('plans:active', 3600, function () {
            return Plan::where('status', 1)->latest()->get()->toArray();
        });
    }

    /**
     * Warm up categories cache
     */
    public function warmupCategories(): void
    {
        $this->cacheService->remember('categories:active', 3600, function () {
            return Category::where('status', 1)->latest()->get()->toArray();
        });
    }

    /**
     * Warm up system settings cache
     */
    public function warmupSystemSettings(): void
    {
        $this->cacheService->remember('system:settings', 3600, function () {
            return Setting::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Warm up cache for specific business
     */
    public function warmupBusiness(int $businessId): void
    {
        try {
            $this->warmupBusinessSettings($businessId);
            $this->warmupBusinessProducts($businessId);
            $this->warmupBusinessParties($businessId);
            $this->warmupBusinessUserPermissions($businessId);
            
            Log::info("Business cache warmed up for business ID: {$businessId}");
        } catch (\Exception $e) {
            Log::error("Business cache warming failed for business ID {$businessId}: " . $e->getMessage());
        }
    }

    /**
     * Warm up business settings cache
     */
    public function warmupBusinessSettings(int $businessId): void
    {
        $this->cacheService->rememberForBusiness($businessId, 'settings', 3600, function () use ($businessId) {
            return Setting::where('business_id', $businessId)->first()?->toArray() ?? [];
        });
    }

    /**
     * Warm up business products cache
     */
    public function warmupBusinessProducts(int $businessId): void
    {
        $this->cacheService->rememberForBusiness($businessId, 'products:list', 1800, function () use ($businessId) {
            return Product::where('business_id', $businessId)
                ->select('id', 'productName', 'productCode', 'sales_price')
                ->where('status', 1)
                ->get()
                ->toArray();
        });
    }

    /**
     * Warm up business parties (customers/suppliers) cache
     */
    public function warmupBusinessParties(int $businessId): void
    {
        $this->cacheService->rememberForBusiness($businessId, 'parties:list', 1800, function () use ($businessId) {
            return \App\Models\Party::where('business_id', $businessId)
                ->select('id', 'name', 'phone', 'type')
                ->where('status', 1)
                ->get()
                ->toArray();
        });
    }

    /**
     * Warm up business user permissions cache
     */
    public function warmupBusinessUserPermissions(int $businessId): void
    {
        $users = User::where('business_id', $businessId)->get();
        
        foreach ($users as $user) {
            $this->cacheService->remember("user_permissions:{$user->id}", 3600, function () use ($user) {
                return $user->getAllPermissions()->pluck('name')->toArray();
            });
        }
    }

    /**
     * Warm up cache for user on login
     */
    public function warmupForUser(User $user): void
    {
        try {
            // Warm up user permissions
            $this->cacheService->remember("user_permissions:{$user->id}", 3600, function () use ($user) {
                return $user->getAllPermissions()->pluck('name')->toArray();
            });

            // Warm up user settings
            $this->cacheService->remember("user_settings:{$user->id}", 3600, function () use ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'business_id' => $user->business_id,
                    'lang' => $user->lang,
                ];
            });

            // If user has business, warm up business cache
            if ($user->business_id) {
                $this->warmupBusiness($user->business_id);
            }

            Log::info("User cache warmed up for user ID: {$user->id}");
        } catch (\Exception $e) {
            Log::error("User cache warming failed for user ID {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Warm up dashboard cache
     */
    public function warmupDashboard(int $businessId): void
    {
        try {
            $this->cacheService->rememberForBusiness($businessId, 'dashboard:summary', 300, function () use ($businessId) {
                return [
                    'total_sales' => \App\Models\Sale::where('business_id', $businessId)->sum('totalAmount'),
                    'total_purchases' => \App\Models\Purchase::where('business_id', $businessId)->sum('totalAmount'),
                    'total_products' => Product::where('business_id', $businessId)->count(),
                    'total_customers' => \App\Models\Party::where('business_id', $businessId)->where('type', 'customer')->count(),
                ];
            });

            Log::info("Dashboard cache warmed up for business ID: {$businessId}");
        } catch (\Exception $e) {
            Log::error("Dashboard cache warming failed for business ID {$businessId}: " . $e->getMessage());
        }
    }

    /**
     * Clear all warmed cache
     */
    public function clearWarmedCache(): void
    {
        $this->cacheService->clearAll();
        Log::info('All warmed cache cleared');
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return $this->cacheService->getCacheStats();
    }
}
