<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmacyExperienceTest extends TestCase
{
    public function test_dashboard_page_is_accessible(): void
    {
        $response = $this->get('/pharmacy-dashboard');

        $response->assertStatus(200);
        $response->assertSee('لوحة تحكم الصيدلية');
    }

    public function test_stock_report_endpoint_is_accessible(): void
    {
        $response = $this->getJson('/api/v1/reports/stock');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary' => ['total_items', 'low_stock_items'],
            'items',
        ]);
    }

    public function test_sales_report_endpoint_is_accessible(): void
    {
        $response = $this->getJson('/api/v1/reports/sales');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary' => ['total_sales', 'total_amount'],
            'items',
        ]);
    }
}
