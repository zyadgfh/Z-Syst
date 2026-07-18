<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportApiTest extends TestCase
{
    public function test_can_view_stock_report(): void
    {
        $response = $this->getJson('/api/v1/reports/stock');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary' => ['total_items', 'low_stock_items'],
            'items',
        ]);
    }
}
