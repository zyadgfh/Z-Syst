<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\TrustProxies;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

/**
 * Guards for the pre-launch security review (items 2, 4, 9, 12, 18, 19, 23, 26).
 *
 * Each test exists because something in the codebase used to make the opposite
 * true; the comment on each one names what that was, so the test is not
 * "fixed" by deleting the behaviour it protects.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    /** adminDecision(): the middleware called $next, so the request went through. */
    private const PASSED = 'passed';

    /** adminDecision(): the middleware refused the request itself. */
    private const DENIED = 'denied';

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
     * `staff` is the low-trust role, and 251 of the 289 named admin routes carry
     * no `permission:` gate — for those, AdminMiddleware is the only thing
     * between an employee account and the action. Before this review staff was
     * in the blanket allowlist, so any employee could reach `users/delete-all`,
     * `users/bulk-change-status`, `system-settings`, `gateways`, `roles` and
     * `plans`.
     *
     * Denied through the real stack, as JSON, so the caller sees the 403 rather
     * than a redirect it would follow and misread as success.
     */
    public function test_staff_is_denied_the_administrative_back_office(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        foreach ([
            '/admin/users',
            '/admin/roles',
            '/admin/permissions',
            '/admin/plans',
            '/admin/system-settings',
            '/admin/gateways',
            '/admin/audit-logs',
            '/admin/business',
            '/admin/subscriptions',
            '/admin/currencies',
            '/admin/banners',
            '/admin/supplier-invoices',
            '/admin/tenant-payment-settings',
        ] as $path) {
            $this->assertSame(403, $this->actingAs($staff)->getJson($path)->getStatusCode(), "$path must be denied to staff");
        }

        // POST-only administrative endpoints, including the destructive ones.
        foreach (['/admin/users/delete-all', '/admin/users/bulk-change-status'] as $path) {
            $this->assertSame(403, $this->actingAs($staff)->postJson($path)->getStatusCode(), "$path must be denied to staff");
        }
    }

    /**
     * The other half of the same decision, and the half that a blanket denial
     * got wrong: `config/supabase.php` gives `staff` `sales-create` and gives
     * `shop-owner` no such ability, so the employee is the only role allowed to
     * ring up a sale — and `admin/pos/*` and `admin/dose/*` have no `permission:`
     * gate, which makes this middleware the till's only door.
     *
     * Exercised against the middleware directly with a synthetic matched route,
     * so the assertion is about the authorization decision and not about whether
     * a controller happens to have its fixtures in order.
     */
    public function test_staff_keeps_the_till_and_the_operational_screens(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);

        foreach ([
            'admin',                       // dashboard-read
            'admin/get-dashboard',
            'admin/dashboard-reports',     // reports-read
            'admin/pos',                   // sales-create — the till
            'admin/pos/checkout',
            'admin/pos/interactions',
            'admin/pos/dose-labels/1',
            'admin/dose/DOSE:1:2',
            'admin/notifications',
            'admin/loyalty',
            'admin/receipts',
            'admin/prescriptions',
            'admin/traceability',
            'admin/warehouses',
            'admin/stock-transfers',
            'admin/barcodes',
            'admin/device-setup',          // printers-read (printers live under it)
            'admin/insurance/claims',      // insurance-claims-read (nested)
            'admin/profile/two-factor',    // self-service
        ] as $uri) {
            $this->assertSame(
                self::PASSED,
                $this->adminDecision($uri),
                "staff must reach /$uri — it is operational and has no gate of its own"
            );
        }

        foreach ([
            'admin/users/delete-all',
            'admin/system-settings',
            'admin/gateways',
            'admin/plans',
            'admin/business',
            'admin/audit-logs',
        ] as $uri) {
            $this->assertSame(
                self::DENIED,
                $this->adminDecision($uri),
                "staff must not reach /$uri"
            );
        }
    }

    /**
     * A route that carries its own authorization gate is delegated to that gate,
     * so `config/supabase.php` stays the single source of truth instead of being
     * duplicated here where it would drift. staff holds 8 of the 38 gated
     * permissions and is refused the other 30 by the gate itself — which is why
     * `IntegrationTest::test_permission_enforcement` still sees its 403 for
     * `products-create`: it comes from the permission layer, not from this one.
     */
    public function test_routes_with_their_own_gate_are_left_to_that_gate(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);

        // Gated: passed through, whatever the permission map later decides.
        // Every area here is deliberately outside STAFF_OPERATIONAL, so it is
        // the gate and not the allowlist that lets the request through.
        $this->assertSame(self::PASSED, $this->adminDecision('admin/products', ['permission:products-create']));
        $this->assertSame(self::PASSED, $this->adminDecision('admin/promotions', ['permission:promotions-read']));
        $this->assertSame(self::PASSED, $this->adminDecision('admin/store-orders', ['permission:store-orders-read']));
        $this->assertSame(self::PASSED, $this->adminDecision('admin/business/upgrade-plan/1', ['role:superadmin']));

        // The same URIs with the gate taken away fall back to deny-by-default,
        // which is what proves the delegation above is keyed on the gate.
        $this->assertSame(self::DENIED, $this->adminDecision('admin/products'));
        $this->assertSame(self::DENIED, $this->adminDecision('admin/promotions'));
        $this->assertSame(self::DENIED, $this->adminDecision('admin/store-orders'));
    }

    /**
     * The roles that own or administer a tenant keep the whole panel, so the
     * narrowing above is aimed at one role and is not a general lockout.
     */
    public function test_tenant_owners_and_platform_admins_still_reach_the_admin_panel(): void
    {
        foreach (['shop-owner', 'superadmin', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertNotSame(
                403,
                $this->actingAs($user)->getJson('/admin')->getStatusCode(),
                "role '$role' must pass AdminMiddleware"
            );
        }

        // And a role outside the model entirely is refused.
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer);
        $this->assertSame(self::DENIED, $this->adminDecision('admin/pos'));
    }

    /**
     * An allowlist entry that matches no route is a rename waiting to become a
     * hole: the operational area silently moves somewhere the list does not
     * cover, or — worse — the list keeps a stale name while a new administrative
     * route is added under the old one.
     */
    public function test_the_staff_allowlist_has_no_dead_entries(): void
    {
        $uris = [];
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            $uris[] = ltrim((string) $route->uri(), '/');
        }

        foreach (AdminMiddleware::staffOperationalAreas() as $area) {
            if ($area === '') {
                $this->assertContains('admin', $uris, 'the panel root must exist');

                continue;
            }

            $matched = false;
            foreach ($uris as $uri) {
                if ($uri === "admin/$area" || str_starts_with($uri, "admin/$area/")) {
                    $matched = true;
                    break;
                }
            }

            $this->assertTrue($matched, "allowlist entry '$area' matches no admin route — stale or renamed");
        }
    }

    /**
     * Run AdminMiddleware against a synthetic matched route.
     *
     * @param  array<int, string>  $routeMiddleware
     */
    private function adminDecision(string $uri, array $routeMiddleware = ['web', 'auth', 'admin']): int
    {
        $route = new Route(['GET', 'POST'], $uri, fn () => 'passed');
        $route->middleware($routeMiddleware);

        $request = Request::create('/'.$uri, 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setRouteResolver(fn () => $route);

        $response = (new AdminMiddleware)->handle($request, fn () => response('passed'));

        return $response->getContent() === 'passed' ? self::PASSED : self::DENIED;
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
     *
     * proxies() hands Symfony the raw string and the framework splits it on
     * commas, so readProxies() below normalises to an array — and the array is
     * what gets asserted. This test first shipped comparing the bare string
     * against that normalised array and failed on the type alone, so the
     * assertion now states the contract instead of the accident.
     */
    public function test_trusted_proxies_can_be_overridden_by_environment(): void
    {
        $this->setTrustedProxies('203.0.113.7');
        $this->assertSame(['203.0.113.7'], $this->readProxies(new TrustProxies));

        // A comma separated override arrives as one string and is split, with
        // the surrounding whitespace trimmed.
        $this->setTrustedProxies('203.0.113.7, 198.51.100.9');
        $this->assertSame(['203.0.113.7', '198.51.100.9'], $this->readProxies(new TrustProxies));

        $this->clearTrustedProxies();

        // With the override gone the documented defaults come back, so a stale
        // value cannot leak into the next test.
        $this->assertContains('10.0.0.0/8', $this->readProxies(new TrustProxies));
        $this->assertCount(5, $this->readProxies(new TrustProxies));
    }

    /**
     * env() reads through phpdotenv's repository, whose adapters are consulted
     * in a fixed order ($_SERVER, then $_ENV, then getenv()). Set all three so
     * the test does not depend on which adapter happens to win.
     */
    private function setTrustedProxies(string $value): void
    {
        putenv('TRUSTED_PROXIES='.$value);
        $_ENV['TRUSTED_PROXIES'] = $value;
        $_SERVER['TRUSTED_PROXIES'] = $value;
    }

    private function clearTrustedProxies(): void
    {
        putenv('TRUSTED_PROXIES');
        unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
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
