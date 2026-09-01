<?php

namespace Tests\Unit\Services;

use App\Services\CacheTags;
use Tests\TestCase;

class CacheTagsTest extends TestCase
{
    public function test_business_tags_contain_business_id(): void
    {
        $businessId = 42;
        $tags = CacheTags::getBusinessTags($businessId);
        
        $this->assertContains("business:{$businessId}", $tags);
        $this->assertContains("business:{$businessId}:sales", $tags);
        $this->assertContains("business:{$businessId}:products", $tags);
    }

    public function test_business_data_tags(): void
    {
        $businessId = 1;
        $dataType = 'sales';
        
        $tags = CacheTags::getBusinessDataTags($businessId, $dataType);
        
        $this->assertContains("business:{$businessId}:{$dataType}", $tags);
        $this->assertContains($dataType, $tags);
        $this->assertContains("{$dataType}:list", $tags);
    }

    public function test_model_invalidation_tags_for_sale(): void
    {
        $tags = CacheTags::getModelInvalidationTags(\App\Models\Sale::class, 1);
        
        $this->assertContains(CacheTags::SALES, $tags);
        $this->assertContains(CacheTags::SALES_LIST, $tags);
        $this->assertContains(CacheTags::DASHBOARD, $tags);
        $this->assertContains("business:1:sales", $tags);
    }

    public function test_model_invalidation_tags_for_product(): void
    {
        $tags = CacheTags::getModelInvalidationTags(\App\Models\Product::class, 2);
        
        $this->assertContains(CacheTags::PRODUCTS, $tags);
        $this->assertContains(CacheTags::STOCK, $tags);
        $this->assertContains("business:2:products", $tags);
    }

    public function test_ttl_values(): void
    {
        $this->assertEquals(300, CacheTags::getTTL(CacheTags::DASHBOARD));
        $this->assertEquals(300, CacheTags::getTTL(CacheTags::SALES));
        $this->assertEquals(1800, CacheTags::getTTL(CacheTags::PRODUCTS));
        $this->assertEquals(3600, CacheTags::getTTL(CacheTags::SETTINGS));
        $this->assertEquals(600, CacheTags::getTTL('unknown'));
    }

    public function test_constants_are_defined(): void
    {
        $this->assertEquals('dashboard', CacheTags::DASHBOARD);
        $this->assertEquals('sales', CacheTags::SALES);
        $this->assertEquals('products', CacheTags::PRODUCTS);
        $this->assertEquals('stock', CacheTags::STOCK);
        $this->assertEquals('parties', CacheTags::PARTIES);
        $this->assertEquals('settings', CacheTags::SETTINGS);
    }
}
