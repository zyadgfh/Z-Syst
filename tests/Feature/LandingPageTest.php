<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_home_page_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Welcome');
    }

    public function test_features_page_renders_successfully(): void
    {
        $response = $this->get('/docs/z-syst-feature-list');

        $response->assertStatus(200);
        $response->assertSeeText('Z-Syst Product Feature List');
    }
}
