<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_own_profile(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'name' => 'Test User',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Test User',
            ]);
    }

    public function test_user_can_update_own_profile(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile', [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_user_cannot_update_profile_without_required_fields(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile', [
                'name' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_change_password_without_valid_current_password(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'password' => bcrypt('oldpassword'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/change-password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Current password does not match with old password.']);
    }
}
