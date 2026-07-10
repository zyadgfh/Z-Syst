<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_home_page_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Z-Syst');
        $response->assertSee('View full feature list');
    }

    public function test_features_page_renders_successfully(): void
    {
        $response = $this->get('/features');

        $response->assertStatus(200);
        $response->assertSeeText('Z-Syst Features');
        $response->assertSeeText('Offline & Remote Access', false);
        $response->assertSeeText('Inventory & Product Intelligence', false);
    }
}
