<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\TwoFactorAuth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TwoFactorAuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create(['business_category_id' => $category->id]);
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
    }

    public function test_2fa_status_returns_disabled_when_not_setup(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/2fa/status');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_enabled' => false,
                ],
            ]);
    }

    public function test_2fa_setup_generates_secret(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/2fa/setup');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['secret', 'otpauth_url', 'recovery_codes'],
            ]);

        $this->assertDatabaseHas('two_factor_auth', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_2fa_confirm_rejects_invalid_code(): void
    {
        Sanctum::actingAs($this->user);

        // Setup first
        $this->postJson('/api/v1/2fa/setup');

        // Try to confirm with invalid code
        $response = $this->postJson('/api/v1/2fa/confirm', [
            'code' => '000000',
        ]);

        $response->assertUnprocessable();
    }

    public function test_2fa_disable_requires_password(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/2fa/disable');

        $response->assertUnprocessable();
    }

    public function test_2fa_setup_rejects_when_already_enabled(): void
    {
        Sanctum::actingAs($this->user);

        // Create enabled 2FA
        TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'secret' => 'JBSWY3DPEHPK3PXP',
            'is_enabled' => true,
            'enabled_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/2fa/setup');

        $response->assertStatus(409);
    }

    public function test_2fa_status_returns_enabled_when_active(): void
    {
        Sanctum::actingAs($this->user);

        TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'secret' => 'JBSWY3DPEHPK3PXP',
            'is_enabled' => true,
            'enabled_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/2fa/status');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_enabled' => true,
                ],
            ]);
    }
}
