<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmacyDashboardTest extends TestCase
{
    public function test_pharmacy_medicines_page_is_accessible(): void
    {
        $response = $this->get('/pharmacy/medicines');

        $response->assertStatus(200);
    }
}
