<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards for the pre-launch security review (items 4, 9, 12, 18, 19, 23).
 *
 * Each test exists because something in the codebase used to make the opposite
 * true; the comment on each one names what that was, so the test is not
 * "fixed" by deleting the behaviour it protects.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * safiull/laravel-installer registers /install, /update and /envato from its
     * own service provider and guards them only with
     * file_exists(storage_path('installed')) — a git-ignored file, absent on any
     * freshly booted container. POST /install/environment/saveClassic rewrites
     * .env; GET /update/final sits outside the guard on purpose and runs
     * migrate --force. BlockInstallerRoutes and the nginx location both refuse
     * the prefixes; this is the framework layer.
     */
    public function test_setup_wizard_routes_are_unreachable(): void
    {
        foreach ([
            '/install',
            '/install/environment/classic',
            '/install/environment/wizard',
            '/install/database',
            '/install/final',
            '/update',
            '/update/final',
            '/update/update-process',
            '/envato/code-verification',
        ] as $path) {
            $this->assertSame(404, $this->get($path)->getStatusCode(), "$path must be refused");
            $this->assertSame(404, $this->post($path)->getStatusCode(), "$path must be refused on POST too");
        }
    }

    /**
     * The block is anchored on the first path segment, so an application route
     * that merely shares the opening letters keeps working, and unrelated routes
     * are untouched by the new global middleware.
     */
    public function test_blocking_the_wizard_does_not_block_application_routes(): void
    {
        $this->get('/api/up')->assertOk();
        $this->get('/')->assertOk();
    }

    /**
     * {gateway} reaches config() key interpolation and a BusinessSetting key, so
     * it is kept inside a closed set; an unknown name must fail closed rather
     * than fall through to a signature scheme written for another gateway.
     */
    public function test_gateway_allowlist_is_closed(): void
    {
        foreach (['paymob', 'fawry', 'instapay'] as $supported) {
            $this->assertTrue(PaymentGatewayService::isSupported($supported), "$supported is supported");
        }

        foreach (['paypal', 'PAYMOB', 'paymob ', '', 'app', 'services.paymob', 'database'] as $rejected) {
            $this->assertFalse(PaymentGatewayService::isSupported($rejected), "'$rejected' must be rejected");
        }
    }

    /**
     * The callback route constrains the segment, so a gateway outside the
     * allowlist never reaches the controller — no transaction lookup, no
     * config() interpolation.
     */
    public function test_callback_for_unknown_gateway_is_not_found(): void
    {
        $this->get('/gateway/paypal/callback/1')->assertNotFound();
        $this->get('/gateway/../../etc/callback/1')->assertNotFound();
    }

    /**
     * AdminMiddleware used to answer every denial with redirect('/'), which an
     * API client follows and reads as success. A JSON caller must see the 403.
     */
    public function test_admin_panel_denies_json_callers_with_403_instead_of_a_redirect(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)
            ->getJson('/admin')
            ->assertStatus(403);
    }

    /**
     * Trusting '*' would let a client pick its own X-Forwarded-For (the platform
     * appends the real peer, so with every hop trusted the leftmost, forged entry
     * wins) — that is a rate-limit bypass and an audit-log forgery. Trusting
     * nothing makes $request->ip() the proxy for everybody, so throttle:10,1 on
     * sign-in becomes one shared bucket. Private ranges only is the setting that
     * gets both right.
     */
    public function test_trusted_proxies_default_to_private_ranges_only(): void
    {
        $proxies = $this->readProxies(new TrustProxies);

        $this->assertNotContains('*', $proxies, 'wildcard trust allows X-Forwarded-For spoofing');
        $this->assertContains('10.0.0.0/8', $proxies);
        $this->assertContains('172.16.0.0/12', $proxies);
        $this->assertContains('192.168.0.0/16', $proxies);

        foreach ($proxies as $range) {
            $this->assertMatchesRegularExpression(
                '/^(10\.0\.0\.0\/8|172\.16\.0\.0\/12|192\.168\.0\.0\/16|127\.0\.0\.1|::1)$/',
                (string) $range,
                'only private/loopback ranges may be trusted by default'
            );
        }
    }

    /**
     * The deployment can still widen trust through the environment without a
     * code change, which is what makes the default safe to keep strict.
     */
    public function test_trusted_proxies_can_be_overridden_by_environment(): void
    {
        config()->set('app.env', 'testing');
        putenv('TRUSTED_PROXIES=203.0.113.7');
        $_ENV['TRUSTED_PROXIES'] = '203.0.113.7';

        $this->assertSame('203.0.113.7', $this->readProxies(new TrustProxies));

        putenv('TRUSTED_PROXIES');
        unset($_ENV['TRUSTED_PROXIES']);
    }

    /**
     * proxies() is protected in the framework middleware; read it the way the
     * framework does rather than widening its visibility for a test.
     *
     * @return array<int, string>|string|null
     */
    private function readProxies(TrustProxies $middleware): array|string|null
    {
        $method = new \ReflectionMethod($middleware, 'proxies');
        $method->setAccessible(true);
        $value = $method->invoke($middleware);

        return is_string($value) ? array_map('trim', explode(',', $value)) : $value;
    }
}
