<?php

namespace App\Services;

/**
 * Cache Tags Registry - Centralized cache tag definitions
 * 
 * Provides consistent, hierarchical cache tags for better invalidation control
 */
class CacheTags
{
    // ===== Dashboard Tags =====
    public const DASHBOARD = 'dashboard';
    public const DASHBOARD_ADMIN = 'dashboard:admin';
    public const DASHBOARD_BUSINESS = 'dashboard:business';
    public const DASHBOARD_STATS = 'dashboard:stats';
    
    // ===== Sales Tags =====
    public const SALES = 'sales';
    public const SALES_LIST = 'sales:list';
    public const SALES_REPORT = 'sales:report';
    public const SALES_STATISTICS = 'sales:statistics';
    
    // ===== Purchase Tags =====
    public const PURCHASES = 'purchases';
    public const PURCHASES_LIST = 'purchases:list';
    public const PURCHASES_REPORT = 'purchases:report';
    public const PURCHASES_STATISTICS = 'purchases:statistics';
    
    // ===== Product Tags =====
    public const PRODUCTS = 'products';
    public const PRODUCTS_LIST = 'products:list';
    public const PRODUCTS_CATALOG = 'products:catalog';
    
    // ===== Stock Tags =====
    public const STOCK = 'stock';
    public const STOCK_LIST = 'stock:list';
    public const STOCK_INVENTORY = 'stock:inventory';
    
    // ===== Party (Customer/Supplier) Tags =====
    public const PARTIES = 'parties';
    public const CUSTOMERS = 'customers';
    public const SUPPLIERS = 'suppliers';
    
    // ===== Financial Tags =====
    public const INCOME = 'income';
    public const EXPENSE = 'expense';
    public const FINANCIAL = 'financial';
    
    // ===== User & Auth Tags =====
    public const USERS = 'users';
    public const PERMISSIONS = 'permissions';
    public const SETTINGS = 'settings';
    
    // ===== Subscription Tags =====
    public const SUBSCRIPTIONS = 'subscriptions';
    public const PLANS = 'plans';
    
    // ===== Category Tags =====
    public const CATEGORIES = 'categories';
    
    // ===== Warehouse Tags =====
    public const WAREHOUSES = 'warehouses';
    
    // ===== Report Tags =====
    public const REPORTS = 'reports';
    public const REPORTS_PURCHASE = 'reports:purchase';
    public const REPORTS_SALES = 'reports:sales';
    public const REPORTS_INVENTORY = 'reports:inventory';
    
    // ===== Insurance Tags =====
    public const INSURANCE = 'insurance';
    
    // ===== Loyalty Tags =====
    public const LOYALTY = 'loyalty';
    
    // ===== Traceability Tags =====
    public const TRACEABILITY = 'traceability';
    
    /**
     * Get all tags for a specific business
     */
    public static function getBusinessTags(int $businessId): array
    {
        return [
            "business:{$businessId}",
            "business:{$businessId}:sales",
            "business:{$businessId}:purchases",
            "business:{$businessId}:products",
            "business:{$businessId}:stock",
            "business:{$businessId}:customers",
            "business:{$businessId}:suppliers",
            "business:{$businessId}:dashboard",
        ];
    }
    
    /**
     * Get tags for specific data type with business context
     */
    public static function getBusinessDataTags(int $businessId, string $dataType): array
    {
        return [
            "business:{$businessId}:{$dataType}",
            "{$dataType}",
            "{$dataType}:list",
        ];
    }
    
    /**
     * Get invalidation tags for model
     */
    public static function getModelInvalidationTags(string $modelClass, ?int $businessId = null): array
    {
        $shortName = class_basename($modelClass);
        
        $tags = match($shortName) {
            'Sale' => [self::SALES, self::SALES_LIST, self::SALES_REPORT, self::DASHBOARD],
            'Purchase' => [self::PURCHASES, self::PURCHASES_LIST, self::PURCHASES_REPORT, self::DASHBOARD],
            'Product' => [self::PRODUCTS, self::PRODUCTS_LIST, self::PRODUCTS_CATALOG, self::STOCK],
            'Stock' => [self::STOCK, self::STOCK_LIST, self::STOCK_INVENTORY, self::PRODUCTS],
            'Party' => [self::PARTIES, self::CUSTOMERS, self::SUPPLIERS],
            'Income' => [self::INCOME, self::FINANCIAL, self::DASHBOARD],
            'Expense' => [self::EXPENSE, self::FINANCIAL, self::DASHBOARD],
            'User' => [self::USERS, self::PERMISSIONS],
            'PlanSubscribe' => [self::SUBSCRIPTIONS, self::PLANS, self::DASHBOARD],
            'Setting' => [self::SETTINGS],
            'Category' => [self::CATEGORIES],
            'Warehouse' => [self::WAREHOUSES],
            default => [$shortName],
        };
        
        // Add business-specific tags if business ID is provided
        if ($businessId) {
            $businessTags = [];
            foreach ($tags as $tag) {
                $businessTags[] = "business:{$businessId}:{$tag}";
            }
            $tags = array_merge($tags, $businessTags);
        }
        
        return array_unique($tags);
    }
    
    /**
     * Get cache key with business prefix
     */
    public static function businessKey(int $businessId, string $key): string
    {
        return "business:{$businessId}:{$key}";
    }
    
    /**
     * Get TTL based on data type
     */
    public static function getTTL(string $dataType): int
    {
        return match($dataType) {
            self::DASHBOARD => 300,        // 5 minutes
            self::SALES => 300,            // 5 minutes
            self::PURCHASES => 300,        // 5 minutes
            self::PRODUCTS => 1800,        // 30 minutes
            self::STOCK => 300,            // 5 minutes
            self::PARTIES => 1800,         // 30 minutes
            self::INCOME => 300,           // 5 minutes
            self::EXPENSE => 300,          // 5 minutes
            self::SETTINGS => 3600,        // 1 hour
            self::CATEGORIES => 3600,      // 1 hour
            self::WAREHOUSES => 1800,      // 30 minutes
            self::REPORTS => 300,          // 5 minutes
            default => 600,                // 10 minutes
        };
    }
}
