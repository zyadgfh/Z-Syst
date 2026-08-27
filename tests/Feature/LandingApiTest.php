<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Landing\App\Models\Feature;
use Tests\TestCase;

class LandingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Feature::class)) {
            $this->markTestSkipped('Landing module not available');
        }
    }

    /**
     * Test landing API returns required data structure
     */
    public function test_landing_api_returns_required_data()
    {
        Feature::factory()->create(['status' => 1]);
        Plan::factory()->create(['status' => 1]);

        $response = $this->getJson('/api/v1/landing');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'features',
                    'plans',
                    'testimonials',
                    'recent_blogs',
                ],
                'meta' => [
                    'version',
                    'timestamp',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test pricing API returns plans with discount calculations
     */
    public function test_pricing_api_returns_plans_with_discounts()
    {
        $plan = Plan::factory()->create([
            'status' => 1,
            'subscriptionPrice' => 100,
            'offerPrice' => 80,
        ]);

        $response = $this->getJson('/api/v1/landing/pricing');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'plans' => [
                        '*' => [
                            'id',
                            'name',
                            'duration',
                            'price',
                            'offer_price',
                            'discount_percentage',
                        ],
                    ],
                ],
            ]);

        $responseData = $response->json('data.plans.0');
        $this->assertEquals(20, $responseData['discount_percentage']);
    }

    /**
     * Test features API returns active features
     */
    public function test_features_api_returns_active_features()
    {
        Feature::factory()->create(['status' => 1]);
        Feature::factory()->create(['status' => 0]); // Inactive

        $response = $this->getJson('/api/v1/landing/features');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $featuresCount = count($response->json('data.features'));
        $this->assertEquals(1, $featuresCount);
    }

    /**
     * Test contact API returns contact information
     */
    public function test_contact_api_returns_contact_info()
    {
        $response = $this->getJson('/api/v1/landing/contact');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'contact',
                    'general',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test landing API handles empty data gracefully
     */
    public function test_landing_api_handles_empty_data()
    {
        $response = $this->getJson('/api/v1/landing');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Should return empty arrays even when no data exists
        $this->assertIsArray($response->json('data.features'));
        $this->assertIsArray($response->json('data.plans'));
    }
}
