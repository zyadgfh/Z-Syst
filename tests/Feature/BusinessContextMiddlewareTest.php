<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessContextMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_business_id_cannot_access_business_context_routes(): void
    {
        $user = User::factory()->create(['business_id' => null]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile')
            ->assertStatus(403)
            ->assertJson(['message' => 'Business context is required.']);
    }

    public function test_user_with_business_id_can_access_business_context_routes(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile')
            ->assertStatus(200);
    }

    public function test_report_endpoint_requires_business_context(): void
    {
        $user = User::factory()->create(['business_id' => null]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/purchase-report')
            ->assertStatus(403)
            ->assertJson(['message' => 'Business context is required.']);
    }
}
