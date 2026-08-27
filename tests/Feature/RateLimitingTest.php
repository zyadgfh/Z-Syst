<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_endpoint_has_rate_limiting(): void
    {
        // sign-in has throttle:5,1 middleware
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/sign-in', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response->assertStatus(429);
    }

    public function test_sign_up_endpoint_has_rate_limiting(): void
    {
        // sign-up has throttle:3,1 middleware
        for ($i = 0; $i < 4; $i++) {
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
        // send-reset-code has throttle:5,5 middleware (5 per 5 min)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/send-reset-code', [
                'reset_value' => 'test@example.com',
            ]);
        }

        $response->assertStatus(429);
    }
}
