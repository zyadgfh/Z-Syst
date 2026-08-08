<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_endpoint_has_rate_limiting(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $response = $this->postJson('/api/v1/sign-in', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response->assertStatus(429);
    }

    public function test_sign_up_endpoint_has_rate_limiting(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $response = $this->postJson('/api/v1/sign-up', [
                'name' => 'Test User',
                'email' => "test$i@example.com",
                'phone' => "555-000$i",
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
        }

        $response->assertStatus(429);
    }

    public function test_password_reset_endpoints_have_rate_limiting(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/send-reset-code', [
                'reset_value' => 'test@example.com',
            ]);
        }

        $response->assertStatus(429);
    }
}
