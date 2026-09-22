<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductAnalyticsTest extends TestCase
{
    public function test_analytics_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/product-analytics?period=month')->assertUnauthorized();
    }
}
