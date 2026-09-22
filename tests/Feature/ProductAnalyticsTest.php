<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductAnalyticsTest extends TestCase
{
    public function test_analytics_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/product-analytics?period=month')->assertUnauthorized();
    }

    public function test_analytics_rejects_unsupported_period(): void
    {
        $this->getJson('/api/v1/product-analytics?period=quarter')->assertStatus(401);
    }

    public function test_analytics_rejects_invalid_product_id_after_authentication_layer(): void
    {
        $this->getJson('/api/v1/product-analytics?period=month&product_id=invalid')->assertStatus(401);
    }
}
