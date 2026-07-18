<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmacyPurchasesPageTest extends TestCase
{
    public function test_pharmacy_purchases_page_is_accessible(): void
    {
        $response = $this->get('/pharmacy/purchases');

        $response->assertStatus(200);
    }
}
