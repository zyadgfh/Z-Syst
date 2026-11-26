<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\GatewayTransaction;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Integrations\FawryService;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fawry wiring (RUNBOOK §10.4): FawryService creates the order at initiate(),
 * the outcome-not-bound branch corroborates through the server-to-server status
 * query, and Fawry's native Server Notification V2 settles from its signed
 * messageSignature — the one fawry scheme whose signature DOES cover
 * orderStatus. The generic sha256(merchantCode+reference+amount+secret) scheme
 * still does not cover the outcome (SecurityHardeningTest pins that), so no
 * test here ever trusts a bare `success` flag.
 */
class FawryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected int $businessId;

    protected function setUp(): void
    {
        parent::setUp();

        config(['payments.automatic_enabled' => true]);

        $this->user = User::factory()->create();
        $this->businessId = (int) $this->user->business_id;

        // Non-production mode() reads the tenant setting: these three keys put
        // fawry in live mode, with the same merchant code and secret the
        // config-based FawryService uses once enabled.
        BusinessSetting::put($this->businessId, 'fawry_api_key', 'test-key');
        BusinessSetting::put($this->businessId, 'fawry_merchant_code', 'M1');
        BusinessSetting::put($this->businessId, 'fawry_hmac_secret', 'topsecret');
    }

    /** Credentials on: FawryService.enabled() becomes true, pointed at a fake host. */
    private function enableFawry(): void
    {
        config([
            'integrations.fawry.enabled' => true,
            'integrations.fawry.merchant_code' => 'M1',
            'integrations.fawry.secure_key' => 'topsecret',
            'integrations.fawry.base_url' => 'https://fawry.test',
        ]);
    }

    private function tx(float $amount = 300.0, ?string $reference = 'REF1', ?int $payableId = null): GatewayTransaction
    {
        $tx = GatewayTransaction::create([
            'business_id' => $this->businessId,
            'gateway' => 'fawry',
            'purpose' => 'subscription',
            'payable_id' => $payableId,
            'amount' => $amount,
            'currency' => 'EGP',
            'status' => 'pending',
        ]);

        if ($reference !== null) {
            $tx->update(['reference' => $reference]);
        }

        return $tx;
    }

    /**
     * A genuine Server Notification V2 payload, signed with the test secret —
     * exactly the fields Fawry documents, including their field spellings.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function notification(array $overrides = []): array
    {
        $params = array_merge([
            'fawryRefNumber' => '963455678',
            'merchantRefNumber' => 'REF1',
            'paymentAmount' => '300.00',
            'orderAmount' => '300.00',
            'orderStatus' => 'PAID',
            'paymentMethod' => 'PayAtFawry',
            'paymentRefrenceNumber' => '369552233',
        ], $overrides);

        $params['messageSignature'] = FawryService::notificationSignature($params, 'topsecret');

        return $params;
    }

    // ── Native Server Notification V2 (messageSignature covers orderStatus) ──

    public function test_native_notification_paid_is_settled_because_signature_covers_order_status(): void
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Pro',
            'slug' => 'pro-'.uniqid(),
            'price' => 300,
            'currency' => 'EGP',
        ]);
        $subscription = Subscription::create([
            'business_id' => $this->businessId,
            'plan_id' => $plan->id,
            'status' => 'past_due',
            'starts_at' => now()->toDateString(),
        ]);
        $tx = $this->tx(300.0, 'REF1', $subscription->id);

        $settled = app(PaymentGatewayService::class)->handleCallback('fawry', $tx->id, $this->notification());

        $this->assertSame('paid', $settled->status);
        $this->assertNotNull($settled->paid_at);
        $this->assertSame('fawry_notification', $settled->payload['settled_via'] ?? null);
        // The entitlement itself: a paid subscription transaction activates it.
        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_native_notification_with_tampered_order_status_is_rejected(): void
    {
        $tx = $this->tx();

        // Sign as a DECLINE, deliver as PAID. orderStatus is inside the signed
        // material, so the signature no longer matches and nothing is granted —
        // this is the exact property the generic scheme lacks.
        $params = $this->notification(['orderStatus' => 'FAILED']);
        $params['orderStatus'] = 'PAID';

        $settled = app(PaymentGatewayService::class)->handleCallback('fawry', $tx->id, $params);

        $this->assertSame('failed', $settled->status);
        $this->assertSame('invalid_notification_signature', $settled->payload['reason'] ?? null);
        $this->assertFalse($settled->payload['verified'] ?? true);
        $this->assertNull($settled->paid_at);
    }

    public function test_native_notification_expired_marks_the_transaction_failed(): void
    {
        $tx = $this->tx();

        // An authoritative signed negative: the order is dead, nothing was
        // granted, so recording the decline cannot invite a double charge.
        $settled = app(PaymentGatewayService::class)->handleCallback('fawry', $tx->id, $this->notification(['orderStatus' => 'EXPIRED']));

        $this->assertSame('failed', $settled->status);
        $this->assertSame('fawry_notification_EXPIRED', $settled->payload['reason'] ?? null);
        $this->assertNull($settled->paid_at);
    }

    public function test_native_notification_for_a_different_reference_fails(): void
    {
        $tx = $this->tx(300.0, 'REF1');

        // Signature is valid — it is a genuine Fawry notification — but about
        // another order of ours. It must not settle this row.
        $settled = app(PaymentGatewayService::class)->handleCallback('fawry', $tx->id, $this->notification(['merchantRefNumber' => 'OTHER']));

        $this->assertSame('failed', $settled->status);
        $this->assertSame('reference_mismatch', $settled->payload['reason'] ?? null);
    }

    // ── Generic scheme + server-to-server status query corroboration ────────

    public function test_generic_callback_is_settled_by_the_server_to_server_status_query(): void
    {
        $this->enableFawry();
        $tx = $this->tx();

        Http::fake([
            '*ECommerceWeb/Fawry/payments/status/v2*' => Http::response([
                'statusCode' => 200,
                'orderStatus' => 'PAID',
                'orderAmount' => 300.00,
                'paymentAmount' => 300.00,
            ], 200),
        ]);

        // The generic signature verifies (we know the secret and the amount),
        // but it says nothing about the outcome — so the branch queries Fawry
        // and settles from the authenticated response instead of the flag.
        $signature = hash('sha256', 'M1REF1300topsecret');
        $settled = app(PaymentGatewayService::class)->handleCallback('fawry', $tx->id, [
            'success' => 'true',
            'reference' => 'REF1',
            'signature' => $signature,
        ]);

        $this->assertSame('paid', $settled->status);
        $this->assertNotNull($settled->paid_at);
        $this->assertSame('fawry_status_query', $settled->payload['settled_via'] ?? null);

        // The query itself must be signed: sha256(merchantCode + merchantRefNumber + secureKey).
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'payments/status/v2')
                && str_contains($request->url(), 'signature='.hash('sha256', 'M1REF1topsecret'));
        });
    }

    public function test_status_query_inconclusive_keeps_the_transaction_pending(): void
    {
        $this->enableFawry();
        $tx = $this->tx();

        Http::fake([
            '*ECommerceWeb/Fawry/payments/status/v2*' => Http::response([
                'statusCode' => 200,
                'orderStatus' => 'NEW',
            ], 200),
        ]);

        $settled = app(PaymentGatewayService::class)->handleCallback('fawry', $tx->id, [
            'success' => 'true',
            'reference' => 'REF1',
            'signature' => hash('sha256', 'M1REF1300topsecret'),
        ]);

        // Inconclusive query = unknown, not "not paid": pending with the
        // original reason, no entitlement, open for reconciliation.
        $this->assertSame('pending', $settled->status);
        $this->assertSame('outcome_not_bound_to_signature', $settled->payload['reason'] ?? null);
        $this->assertNull($settled->paid_at);
    }

    public function test_status_query_amount_mismatch_does_not_settle(): void
    {
        $this->enableFawry();
        $tx = $this->tx(300.0);

        Http::fake([
            '*ECommerceWeb/Fawry/payments/status/v2*' => Http::response([
                'statusCode' => 200,
                'orderStatus' => 'PAID',
                'orderAmount' => 999.00,
            ], 200),
        ]);

        $settled = app(PaymentGatewayService::class)->handleCallback('fawry', $tx->id, [
            'success' => 'true',
            'reference' => 'REF1',
            'signature' => hash('sha256', 'M1REF1300topsecret'),
        ]);

        // A signed PAID for someone else's money is not this transaction's
        // money: do not grant, do not fail — leave it for reconciliation.
        $this->assertSame('pending', $settled->status);
        $this->assertNull($settled->paid_at);
    }

    // ── initiate(): the charge that creates the order to bind to ────────────

    public function test_initiate_creates_the_fawry_order_and_stores_the_reference(): void
    {
        $this->enableFawry();
        Http::fake([
            '*ECommerceWeb/Fawry/payments/charge*' => Http::response([
                'statusCode' => 200,
                'statusDescription' => 'Success',
                'referenceNumber' => 'FR99',
                'orderStatus' => 'NEW',
            ], 200),
        ]);

        $tx = app(PaymentGatewayService::class)->initiate($this->businessId, 'fawry', 'subscription', null, 300.0);

        $this->assertSame('pending', $tx->status);
        // Our merchantRefNum — unique per transaction, the key everything
        // downstream (notification, status query, callback) binds to.
        $this->assertMatchesRegularExpression('/^ZS\d+/', (string) $tx->reference);
        $this->assertSame('FR99', $tx->payload['fawry']['referenceNumber'] ?? null);

        Http::assertSent(function ($request) use ($tx) {
            if (! str_contains($request->url(), 'payments/charge')) {
                return false;
            }

            $data = $request->data();

            // The published scheme: sha256(merchantCode + merchantRefNum +
            // customerProfileId + paymentMethod + amount(2dp) + secureKey).
            $expected = hash('sha256', 'M1'.$data['merchantRefNum'].$data['customerProfileId'].$data['paymentMethod'].$data['amount'].'topsecret');

            return $data['merchantRefNum'] === $tx->reference
                && $data['signature'] === $expected;
        });
    }

    public function test_initiate_records_a_failed_transaction_when_the_charge_fails(): void
    {
        $this->enableFawry();
        Http::fake([
            '*ECommerceWeb/Fawry/payments/charge*' => Http::response([
                'statusCode' => 9946,
                'statusDescription' => 'Blank or invalid signature',
            ], 200),
        ]);

        $tx = app(PaymentGatewayService::class)->initiate($this->businessId, 'fawry', 'subscription', null, 300.0);

        // Failed, not left pending: an order that was never created must never
        // be able to settle later, and the caller gets the reason.
        $this->assertSame('failed', $tx->status);
        $this->assertSame('fawry_charge_failed', $tx->payload['reason'] ?? null);
        $this->assertStringContainsString('Blank or invalid signature', (string) ($tx->payload['error'] ?? ''));
        $this->assertNull($tx->reference);
    }

    public function test_initiate_without_credentials_makes_no_http_call(): void
    {
        // FawryService stays disabled (config default) even though mode() is
        // live from the tenant key: no charge, no reference, nothing sent.
        Http::fake();

        $tx = app(PaymentGatewayService::class)->initiate($this->businessId, 'fawry', 'subscription', null, 300.0);

        Http::assertNothingSent();
        $this->assertNull($tx->reference);
        $this->assertSame('pending', $tx->status);
        $this->assertSame('live', $tx->payload['mode'] ?? null);
    }

    // ── HTTP edge: Fawry POSTs the notification to the callback route ───────

    public function test_fawry_notification_posts_to_the_callback_route_and_settles(): void
    {
        $tx = $this->tx();

        $response = $this->post(
            route('web.gateway.callback', ['fawry', $tx->id], false),
            $this->notification()
        );

        // POST is allowed (route match) and CSRF-exempt for this path — a
        // server-to-server gateway cannot present a session token.
        $response->assertOk();

        $fresh = $tx->fresh();
        $this->assertSame('paid', $fresh->status);
        $this->assertNotNull($fresh->paid_at);
    }
}
