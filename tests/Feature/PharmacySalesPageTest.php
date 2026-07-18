<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmacySalesPageTest extends TestCase
{
    public function test_pharmacy_sales_page_is_accessible(): void
    {
        $response = $this->get('/pharmacy/sales');

        $response->assertStatus(200);
    }
}
