<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_without_manage_pages_settings(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
