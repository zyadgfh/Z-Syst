<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Currency;
use App\Models\Gateway;
use App\Models\GatewayTransaction;
use App\Models\Plan;
use App\Models\PlanSubscribe;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Database\Seeders\GatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * The application runs on manual payment: the customer submits a request with a
 * receipt and a platform superadmin approves it. Automatic, gateway-hosted
 * checkout is switched off by `config/payments.php` and this suite is what keeps
 * it off.
 *
 * Two payment systems exist and both have to be refused, because a switch on one
 * leaves the other open:
 *
 *   * the `gateways` table drives /payments-gateways, where `is_manual = 1` takes
 *     the manual branch and a `namespace` calls App\Library\*::make_payment();
 *   * PaymentGatewayService (paymob / fawry / instapay) is automatic-only, entered
 *     from the admin checkout endpoint and settled by the public callback route.
 *
 * The last two tests cover the manual side, which is the half that has to keep
 * working — and to be trustworthy. Approving a payment request extends a
 * business's subscription, and PlanSubscribe is TenantScoped, so without a role
 * gate the person who submitted a request is exactly the person the scope lets
 * approve it.
 */
class ManualPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Every test here asserts the shipped default. OperationsTest turns the
        // switch on to exercise the gateway machinery itself.
        config(['payments.automatic_enabled' => false]);
    }

    private function currency(): Currency
    {
        return Currency::firstOrCreate(
            ['code' => 'EGP'],
            [
                'name' => 'Egyptian Pound',
                'country_name' => 'Egypt',
                'rate' => 1,
                'symbol' => 'E£',
                'position' => 'left',
                'status' => 1,
                'is_default' => 1,
            ]
        );
    }

    private function gateway(array $attributes): Gateway
    {
        return Gateway::create(array_merge([
            'name' => 'Test Gateway',
            'currency_id' => $this->currency()->id,
            'mode' => 1,
            'status' => 1,
            'charge' => 0,
            'data' => '[]',
            'manual_data' => '[]',
            'is_manual' => 0,
            'accept_img' => 0,
            'namespace' => null,
            'phone_required' => 0,
        ], $attributes));
    }

    private function manualGateway(): Gateway
    {
        return $this->gateway([
            'name' => 'Manual',
            'is_manual' => 1,
            'accept_img' => 1,
            'namespace' => null,
            'manual_data' => '{"label":["Bank Name","Transaction ID"],"is_required":["1","1"]}',
        ]);
    }

    private function automaticGateway(string $name = 'Fawry'): Gateway
    {
        return $this->gateway([
            'name' => $name,
            'is_manual' => 0,
            'namespace' => 'App\\Library\\Fawry',
        ]);
    }

    /**
     * The switch defaults to off, and the environment variable is the only way
     * to turn it on — no code change, no admin screen. Asserted against the
     * config file itself so that flipping the default in a later commit cannot
     * pass quietly.
     */
    public function test_automatic_payments_are_off_by_default(): void
    {
        $this->assertFalse(
            (bool) config('payments.automatic_enabled'),
            'automatic payments must be off unless PAYMENTS_AUTOMATIC_ENABLED says otherwise'
        );

        $source = (string) file_get_contents(config_path('payments.php'));
        $this->assertStringContainsString("env('PAYMENTS_AUTOMATIC_ENABLED', false)", $source);
    }

    /**
     * The seeder used to call Gateway::insert(), so every run appended a second
     * copy of all seven rows — and checkout lists every row with status = 1, so
     * a re-run duplicated every payment method on screen. It also seeded all
     * seven enabled, which switched on six providers that have no credentials
     * here. Both halves are asserted: re-running is a no-op on row count, and
     * only the manual method comes out enabled.
     */
    public function test_the_seeder_is_idempotent_and_leaves_automatic_methods_off(): void
    {
        $this->seed(GatewaySeeder::class);
        $this->seed(GatewaySeeder::class);

        $this->assertSame(7, Gateway::count(), 'seeding twice must not duplicate the payment methods');

        $manual = Gateway::where('is_manual', 1)->get();
        $this->assertCount(1, $manual);
        $this->assertSame(1, (int) $manual->first()->status, 'the manual method is the live path and must be enabled');

        $automatic = Gateway::where('is_manual', 0)->get();
        $this->assertCount(6, $automatic);
        foreach ($automatic as $gateway) {
            $this->assertSame(0, (int) $gateway->status, "{$gateway->name} must be seeded disabled while automatic payments are off");
        }

        // Flipping the switch and re-seeding is how they come back — no edit to
        // the seeder, and still no duplicates.
        config(['payments.automatic_enabled' => true]);
        $this->seed(GatewaySeeder::class);

        $this->assertSame(7, Gateway::count());
        $this->assertSame(6, Gateway::where('is_manual', 0)->where('status', 1)->count());
    }

    /**
     * initiate() is the service-layer refusal. Guarding only the HTTP edge would
     * leave a console command, a queue job or a route added later free to open a
     * transaction and mint a callback URL — and the transaction row is what later
     * settles a subscription, so "cannot be created" is the stronger guarantee.
     */
    public function test_the_service_will_not_open_an_automatic_transaction(): void
    {
        $business = Business::factory()->create();
        $service = app(PaymentGatewayService::class);

        try {
            $service->initiate((int) $business->id, 'paymob', 'subscription', null, 500);
            $this->fail('initiate() must refuse while automatic payments are off');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertDatabaseCount('gateway_transactions', 0);
    }

    /**
     * The callback route is public and unauthenticated. While the switch is off
     * it settles nothing — including a callback with a *valid* signature, because
     * a transaction that should not exist is not made legitimate by being signed.
     *
     * 404 rather than 403: the same answer an unknown gateway gets, so a probe
     * learns nothing about how this deployment is configured.
     */
    public function test_the_callback_endpoint_settles_nothing_while_automatic_is_off(): void
    {
        $business = Business::factory()->create();

        // Created directly, because initiate() correctly refuses to.
        $tx = GatewayTransaction::create([
            'business_id' => $business->id,
            'gateway' => 'paymob',
            'purpose' => 'subscription',
            'amount' => 500,
            'currency' => 'EGP',
            'status' => 'pending',
            'reference' => 'ref-manual-mode',
            'payload' => ['mode' => 'live'],
        ]);

        $response = $this->getJson(route('web.gateway.callback', ['paymob', $tx->id], false).'?success=true');

        $response->assertNotFound();
        $this->assertDatabaseHas('gateway_transactions', [
            'id' => $tx->id,
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    /**
     * The admin checkout endpoint is the only route into initiate(). It answers
     * 403 with the same JSON shape it already uses, so a client can tell "this
     * method is off" from "nothing is due" (422) or a malformed request.
     *
     * Asserted as a superadmin on purpose: the route carries
     * permission:settings-update, an ability no role in config/supabase.php
     * actually grants, so only superadmin (via `*`) can reach the controller at
     * all. A 403 here is therefore the config guard, not the permission gate.
     */
    public function test_the_admin_checkout_endpoint_refuses_with_403(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.subscription.pay'), [
            'gateway' => 'paymob',
        ]);

        $response->assertStatus(403)->assertJson(['success' => false]);
        $this->assertDatabaseCount('gateway_transactions', 0);
    }

    /**
     * A gateway that is listed is a gateway a customer can pick, so the checkout
     * screen is where the automatic methods actually have to disappear.
     */
    public function test_the_checkout_screen_offers_only_manual_methods(): void
    {
        $owner = User::factory()->create(['role' => 'shop-owner']);
        $plan = Plan::factory()->create(['subscriptionPrice' => 250, 'offerPrice' => null]);
        $this->manualGateway();
        $this->automaticGateway();

        $response = $this->actingAs($owner)->get(route('payments-gateways.index', [
            'plan_id' => $plan->id,
            'business_id' => $owner->business_id,
        ]));

        $response->assertOk();
        $response->assertSee('Manual');
        $response->assertDontSee('Fawry');
    }

    /**
     * Filtering the list is not the whole job: anyone holding a gateway_id from
     * before the switch was flipped can still post it. The automatic branch must
     * refuse without reaching App\Library\*::make_payment().
     *
     * The observable is the session key that branch writes immediately before it
     * redirects out to the provider — if it is unset, the provider was never
     * called. Asserting "no redirect to an external host" is not enough, because
     * the refusal is itself a redirect.
     */
    public function test_posting_an_automatic_gateway_never_reaches_the_provider(): void
    {
        $owner = User::factory()->create(['role' => 'shop-owner']);
        $plan = Plan::factory()->create(['subscriptionPrice' => 250, 'offerPrice' => null]);
        $automatic = $this->automaticGateway();

        $response = $this->actingAs($owner)
            ->withSession(['business_id' => (int) $owner->business_id])
            ->post(route('payments-gateways.payment', [
                'plan_id' => $plan->id,
                'gateway_id' => $automatic->id,
            ]));

        $response->assertRedirect(route('order.status', ['status' => 'failed'], false));
        $this->assertNull(session('gateway_id'), 'the provider branch sets this before make_payment(); it must not have run');
        $this->assertDatabaseCount('plan_subscribes', 0);
    }

    /**
     * The hole that makes manual-only safe or unsafe. PlanSubscribe is
     * TenantScoped: a shop-owner sees only their own business's rows — which is
     * exactly the request they just submitted. Without a role gate they could
     * mark it paid themselves and extend business.will_expire without paying.
     * The money for a subscription goes to the platform, so approving it is a
     * platform action.
     */
    public function test_a_tenant_owner_cannot_approve_their_own_payment_request(): void
    {
        $owner = User::factory()->create(['role' => 'shop-owner']);
        $business = Business::findOrFail($owner->business_id);
        $plan = Plan::factory()->create(['duration' => 365, 'subscriptionPrice' => 900]);
        $request = PlanSubscribe::factory()->unpaid()->create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'duration' => $plan->duration,
            'price' => $plan->subscriptionPrice,
            'gateway_id' => $this->manualGateway()->id,
        ]);

        foreach (['admin.subscription-reports.paid', 'admin.manual-payments.paid'] as $route) {
            $response = $this->actingAs($owner)->postJson(route($route, $request->id), [
                'notes' => 'I paid, trust me',
            ]);

            $response->assertForbidden();
        }

        $this->assertDatabaseHas('plan_subscribes', ['id' => $request->id, 'payment_status' => 'unpaid']);
        $this->assertNull(Business::findOrFail($business->id)->will_expire, 'the subscription must not have been extended');
    }

    /**
     * The manual path itself has to work, and has to leave an audit trail.
     *
     * `plan_subscribe_id` was missing from Business::$fillable, so all five call
     * sites that wrote it — including this one — had it dropped silently by mass
     * assignment. The update still looked successful because subscription_date
     * and will_expire beside it were written, which is how it went unnoticed: a
     * business ended up subscribed with no record of which request paid for it.
     * Api/BusinessController wrote nothing but that key, so its whole update was
     * a no-op.
     */
    public function test_a_superadmin_approval_activates_the_subscription_and_records_which_request_paid(): void
    {
        $owner = User::factory()->create(['role' => 'shop-owner']);
        $business = Business::findOrFail($owner->business_id);
        $plan = Plan::factory()->create(['duration' => 365, 'subscriptionPrice' => 900]);
        $request = PlanSubscribe::factory()->unpaid()->create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'duration' => $plan->duration,
            'price' => $plan->subscriptionPrice,
            'gateway_id' => $this->manualGateway()->id,
        ]);

        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.subscription-reports.paid', $request->id), [
            'notes' => 'Bank transfer confirmed against statement 2026-11-15',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('plan_subscribes', ['id' => $request->id, 'payment_status' => 'paid']);

        $business->refresh();
        $this->assertNotNull($business->will_expire, 'the subscription must be extended');
        $this->assertNotNull($business->subscription_date);
        $this->assertSame(
            $request->id,
            (int) $business->plan_subscribe_id,
            'the business must record which payment request activated it'
        );
    }

    /**
     * Rejecting is the same platform action as approving, through the same
     * screens, so it carries the same gate.
     */
    public function test_a_tenant_owner_cannot_reject_requests_either(): void
    {
        $owner = User::factory()->create(['role' => 'shop-owner']);
        $business = Business::findOrFail($owner->business_id);
        $request = PlanSubscribe::factory()->unpaid()->create([
            'business_id' => $business->id,
            'plan_id' => Plan::factory()->create(['duration' => 30])->id,
        ]);

        foreach (['admin.subscription-reports.reject', 'admin.manual-payments.reject'] as $route) {
            $this->actingAs($owner)
                ->postJson(route($route, $request->id), ['notes' => 'not mine to decide'])
                ->assertForbidden();
        }

        $this->assertDatabaseHas('plan_subscribes', ['id' => $request->id, 'payment_status' => 'unpaid']);
    }
}
