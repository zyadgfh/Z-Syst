<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_api_returns_expected_structure(): void
    {
        $response = $this->getJson('/api/v1/landing');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'page_data',
                'features',
                'interfaces',
                'testimonials',
                'recent_blogs',
                'blogs',
                'plans',
                'gateways',
                'general',
                'business_categories',
            ]);
    }

    public function test_landing_api_returns_empty_arrays_when_no_content_exists(): void
    {
        $response = $this->getJson('/api/v1/landing');

        $response->assertStatus(200)
            ->assertJsonPath('features', [])
            ->assertJsonPath('interfaces', [])
            ->assertJsonPath('testimonials', [])
            ->assertJsonPath('recent_blogs', [])
            ->assertJsonPath('blogs', [])
            ->assertJsonPath('plans', [])
            ->assertJsonPath('gateways', [])
            ->assertJsonPath('business_categories', []);
    }
}
