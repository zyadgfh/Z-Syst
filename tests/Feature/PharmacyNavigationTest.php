<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmacyNavigationTest extends TestCase
{
    public function test_pharmacy_navigation_component_renders_links(): void
    {
        $response = $this->get('/pharmacy/medicines');

        $response->assertStatus(200);
        $response->assertSee('/pharmacy-dashboard');
        $response->assertSee('/pharmacy/sales');
    }
}
