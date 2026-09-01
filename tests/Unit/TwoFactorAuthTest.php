<?php

namespace Tests\Unit;

use App\Models\TwoFactorAuth;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected TwoFactorAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TwoFactorAuthService();
    }

    public function test_generate_secret_creates_two_factor_record(): void
    {
        $user = User::factory()->create();

        $result = $this->service->generateSecret($user);

        $this->assertArrayHasKey('secret', $result);
        $this->assertArrayHasKey('otpauth_url', $result);
        $this->assertNotEmpty($result['secret']);
        $this->assertStringContainsString('otpauth://totp/', $result['otpauth_url']);
        $this->assertDatabaseHas('two_factor_auth', ['user_id' => $user->id]);
    }

    public function test_secret_is_32_base32_characters(): void
    {
        $user = User::factory()->create();

        $result = $this->service->generateSecret($user);

        $this->assertEquals(32, strlen($result['secret']));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $result['secret']);
    }

    public function test_verify_code_rejects_invalid_code(): void
    {
        $user = User::factory()->create();
        $this->service->generateSecret($user);

        $result = $this->service->verifyCode($user, '000000');

        $this->assertFalse($result);
    }

    public function test_verify_code_rejects_non_digit_code(): void
    {
        $user = User::factory()->create();
        $this->service->generateSecret($user);

        $result = $this->service->verifyCode($user, 'abcdef');

        $this->assertFalse($result);
    }

    public function test_verify_code_rejects_wrong_length_code(): void
    {
        $user = User::factory()->create();
        $this->service->generateSecret($user);

        $result = $this->service->verifyCode($user, '12345');

        $this->assertFalse($result);
    }

    public function test_is_enabled_returns_false_when_not_setup(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->service->isEnabled($user));
    }

    public function test_generate_recovery_codes_creates_10_codes(): void
    {
        $user = User::factory()->create();

        $codes = $this->service->generateRecoveryCodes($user);

        $this->assertCount(10, $codes);
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);
        }
    }

    public function test_disable_requires_correct_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);
        $this->service->generateSecret($user);
        TwoFactorAuth::where('user_id', $user->id)->update(['is_enabled' => true, 'enabled_at' => now()]);

        $result = $this->service->disable($user, 'wrong-password');

        $this->assertFalse($result);
        $this->assertTrue($this->service->isEnabled($user));
    }

    public function test_disable_with_correct_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);
        $this->service->generateSecret($user);
        TwoFactorAuth::where('user_id', $user->id)->update(['is_enabled' => true, 'enabled_at' => now()]);

        $result = $this->service->disable($user, 'secret-password');

        $this->assertTrue($result);
    }

    public function test_two_factor_auth_model_has_correct_casts(): void
    {
        $twoFactor = new TwoFactorAuth([
            'is_enabled' => true,
            'enabled_at' => now(),
            'last_used_at' => now(),
        ]);

        $this->assertIsBool($twoFactor->is_enabled);
        $this->assertIsBool($twoFactor->is_enabled);
    }

    public function test_two_factor_auth_model_is_enabled_method(): void
    {
        $twoFactor = new TwoFactorAuth(['is_enabled' => false]);
        $this->assertFalse($twoFactor->isEnabled());

        $twoFactor = new TwoFactorAuth(['is_enabled' => true, 'secret' => 'JBSWY3DPEHPK3PXP']);
        $this->assertTrue($twoFactor->isEnabled());
    }

    public function test_two_factor_auth_model_records_verification(): void
    {
        $user = User::factory()->create();
        $twoFactor = TwoFactorAuth::create([
            'user_id' => $user->id,
            'secret' => 'JBSWY3DPEHPK3PXP',
            'is_enabled' => true,
        ]);

        $twoFactor->recordVerification();

        $this->assertNotNull($twoFactor->fresh()->last_used_at);
    }
}
