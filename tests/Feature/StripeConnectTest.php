<?php

namespace Tests\Feature;

use App\Models\StripeConnectedAccount;
use App\Models\User;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\Util\ApiVersion;
use Stripe\Util\Util;
use Tests\TestCase;

/**
 * End-to-end feature and SDK-contract tests for the Stripe Connect V2 integration.
 *
 * Instead of mocking out StripeConnectService, these tests plug a recording
 * Stripe HttpClient into `\Stripe\ApiRequestor::setHttpClient(...)`, exercising
 * the REAL `stripe/stripe-php` v23.0.0 SDK (`StripeClient`, V2 services,
 * `parseEventNotification()`, `Webhook::constructEvent()`, and header/JSON
 * serialization) without making outbound network calls.
 */
class StripeConnectTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeHttpClient $fakeStripe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeStripe = new FakeStripeHttpClient();
        ApiRequestor::setHttpClient($this->fakeStripe);

        // Match CI's local-identity test posture so User::factory() works without GoTrue.
        Config::set('supabase.fallback_eloquent', true);

        // Configure obviously synthetic test-only values (never real secrets).
        Config::set('stripe_connect.secret_key', 'test_secret_key_for_unit_suite');
        Config::set('stripe_connect.thin_webhook_secret', 'test_thin_webhook_secret_local');
        Config::set('stripe_connect.webhook_secret', 'test_billing_webhook_secret_local');
        Config::set('stripe_connect.subscription_price_id', 'price_platform_pro_monthly');
        Config::set('stripe_connect.application_fee_amount', 123);
        Config::set('stripe_connect.default_currency', 'usd');

        // Ensure fresh StripeClient per test.
        app()->forgetInstance(StripeConnectService::class);
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(CurlClient::instance());
        parent::tearDown();
    }

    public function test_sdk_uses_expected_api_version_automatically(): void
    {
        $this->assertSame('2026-10-28.endive', ApiVersion::CURRENT);
    }

    public function test_helpful_error_when_placeholder_values_are_not_configured(): void
    {
        Config::set('stripe_connect.secret_key', '<<<NEEDED:STRIPE_SECRET_KEY>>>');

        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->postJson('/connect/accounts', [
                'display_name' => 'Acme Pharmacy',
                'contact_email' => 'owner@acme.test',
            ])
            ->assertStatus(503)
            ->assertJsonFragment(['error' => 'Stripe Connect configuration error: STRIPE_SECRET_KEY is not configured. Add your Stripe platform secret key (e.g. sk_test_... from https://dashboard.stripe.com/apikeys) to your .env file as STRIPE_SECRET_KEY.']);

        // Dashboard itself stays accessible and surfaces the configuration checklist.
        $this->actingAs($user)
            ->get('/connect')
            ->assertOk()
            ->assertSee('Configuration Checklist')
            ->assertSee('STRIPE_SECRET_KEY')
            ->assertSee('STRIPE_THIN_WEBHOOK_SECRET')
            ->assertSee('STRIPE_WEBHOOK_SECRET')
            ->assertSee('STRIPE_SUBSCRIPTION_PRICE_ID');
    }

    public function test_create_v2_connected_account_sends_exact_v2_payload_and_maps_user_in_db(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->fakeStripe->queueJsonResponse(200, [
            'id' => 'acct_1V2SampleMerchant',
            'object' => 'v2.core.account',
            'display_name' => 'Acme Pharmacy',
            'contact_email' => 'owner@acme.test',
            'dashboard' => 'full',
        ]);

        $response = $this->actingAs($user)->postJson('/connect/accounts', [
            'display_name' => 'Acme Pharmacy',
            'contact_email' => 'owner@acme.test',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'account_id' => 'acct_1V2SampleMerchant',
                'display_name' => 'Acme Pharmacy',
            ]);

        // Assert DB mapping from Z-Syst User (UUID) -> Stripe V2 Account ID.
        $this->assertDatabaseHas('stripe_connected_accounts', [
            'user_id' => $user->id,
            'stripe_account_id' => 'acct_1V2SampleMerchant',
            'display_name' => 'Acme Pharmacy',
            'contact_email' => 'owner@acme.test',
        ]);

        // Inspect exact outgoing SDK request.
        $req = $this->fakeStripe->lastRequest();
        $this->assertSame('post', $req['method']);
        $this->assertStringEndsWith('/v2/core/accounts', $req['url']);
        $this->assertSame('v2', $req['apiMode']);

        // Verify automatic Stripe-Version header from SDK (2026-10-28.endive).
        $this->assertContains('Stripe-Version: 2026-10-28.endive', $req['headers']);

        // Verify ONLY the exact V2 properties were sent and NO top-level `type`.
        $params = $req['params'];
        $this->assertArrayNotHasKey('type', $params);
        $this->assertSame('Acme Pharmacy', $params['display_name']);
        $this->assertSame('owner@acme.test', $params['contact_email']);
        $this->assertSame(['country' => 'us'], $params['identity']);
        $this->assertSame('full', $params['dashboard']);
        $this->assertSame([
            'responsibilities' => [
                'fees_collector' => 'stripe',
                'losses_collector' => 'stripe',
            ],
        ], $params['defaults']);
        $this->assertTrue($params['configuration']['merchant']['capabilities']['card_payments']['requested']);

        // Verify `configuration.customer` serializes to JSON `{}` (object), NOT `[]` (array)!
        $serialized = json_encode(Util::objectsToIds($params, true));
        $this->assertStringContainsString('"customer":{}', (string) $serialized);
    }

    public function test_onboarding_account_link_and_live_status_retrieval(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $account = StripeConnectedAccount::create([
            'user_id' => $user->id,
            'stripe_account_id' => 'acct_1OnboardTest',
            'display_name' => 'Onboard Pharmacy',
            'contact_email' => 'onboard@acme.test',
        ]);

        // 1. Account Link creation (V2 API)
        $this->fakeStripe->queueJsonResponse(200, [
            'object' => 'v2.core.account_link',
            'url' => 'https://connect.stripe.com/setup/e/acct_1OnboardTest/token',
            'account' => 'acct_1OnboardTest',
        ]);

        $this->actingAs($user)
            ->postJson("/connect/accounts/{$account->stripe_account_id}/onboard")
            ->assertOk()
            ->assertJson(['url' => 'https://connect.stripe.com/setup/e/acct_1OnboardTest/token']);

        $linkReq = $this->fakeStripe->lastRequest();
        $this->assertSame('post', $linkReq['method']);
        $this->assertStringEndsWith('/v2/core/account_links', $linkReq['url']);
        $this->assertSame('acct_1OnboardTest', $linkReq['params']['account']);
        $this->assertSame('account_onboarding', $linkReq['params']['use_case']['type']);
        $this->assertSame(
            ['merchant', 'customer'],
            $linkReq['params']['use_case']['account_onboarding']['configurations']
        );
        $this->assertStringContainsString(
            'accountId=acct_1OnboardTest',
            $linkReq['params']['use_case']['account_onboarding']['return_url']
        );

        // 2. Live status retrieval directly from V2 Accounts API
        $this->fakeStripe->queueJsonResponse(200, [
            'id' => 'acct_1OnboardTest',
            'object' => 'v2.core.account',
            'display_name' => 'Onboard Pharmacy',
            'contact_email' => 'onboard@acme.test',
            'configuration' => [
                'merchant' => [
                    'applied' => true,
                    'capabilities' => [
                        'card_payments' => [
                            'status' => 'active',
                        ],
                    ],
                ],
            ],
            'requirements' => [
                'summary' => [
                    'minimum_deadline' => [
                        'status' => 'eventually_due',
                    ],
                ],
                'entries' => [],
            ],
        ]);

        $this->actingAs($user)
            ->getJson("/connect/accounts/{$account->stripe_account_id}/status")
            ->assertOk()
            ->assertJson([
                'account_id' => 'acct_1OnboardTest',
                'card_payments_status' => 'active',
                'ready_to_process_payments' => true,
                'requirements_status' => 'eventually_due',
                'onboarding_complete' => true,
            ]);

        $statusReq = $this->fakeStripe->lastRequest();
        $this->assertSame('get', $statusReq['method']);
        $this->assertStringEndsWith('/v2/core/accounts/acct_1OnboardTest', $statusReq['url']);
        $this->assertSame(['configuration.merchant', 'requirements'], $statusReq['params']['include']);

        // Verify pending/currently_due reports onboarding_complete = false
        $this->fakeStripe->queueJsonResponse(200, [
            'id' => 'acct_1OnboardTest',
            'object' => 'v2.core.account',
            'configuration' => [
                'merchant' => [
                    'applied' => true,
                    'capabilities' => [
                        'card_payments' => ['status' => 'pending'],
                    ],
                ],
            ],
            'requirements' => [
                'summary' => [
                    'minimum_deadline' => ['status' => 'currently_due'],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->getJson("/connect/accounts/{$account->stripe_account_id}/status")
            ->assertOk()
            ->assertJson([
                'ready_to_process_payments' => false,
                'onboarding_complete' => false,
            ]);
    }

    public function test_create_product_list_storefront_and_direct_charge_checkout_use_stripe_account_header(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $account = StripeConnectedAccount::create([
            'user_id' => $user->id,
            'stripe_account_id' => 'acct_1StorefrontDemo',
            'display_name' => 'Sunset Pharmacy',
            'contact_email' => 'sunset@acme.test',
        ]);

        // 1. Create product on connected account (`Stripe-Account` header)
        $this->fakeStripe->queueJsonResponse(200, [
            'id' => 'prod_VitD3',
            'object' => 'product',
            'name' => 'Vitamin D3',
            'description' => 'Daily immune support',
            'default_price' => 'price_VitD3',
        ]);

        $this->actingAs($user)
            ->postJson("/connect/accounts/{$account->stripe_account_id}/products", [
                'name' => 'Vitamin D3',
                'description' => 'Daily immune support',
                'price' => 24.99,
                'currency' => 'usd',
            ])
            ->assertStatus(201)
            ->assertJson(['id' => 'prod_VitD3', 'name' => 'Vitamin D3']);

        $createProdReq = $this->fakeStripe->lastRequest();
        $this->assertSame('post', $createProdReq['method']);
        $this->assertStringEndsWith('/v1/products', $createProdReq['url']);
        $this->assertContains('Stripe-Account: acct_1StorefrontDemo', $createProdReq['headers']);
        $this->assertSame(2499, $createProdReq['params']['default_price_data']['unit_amount']);
        $this->assertSame('usd', $createProdReq['params']['default_price_data']['currency']);

        // 2. Public storefront renders products retrieved with `Stripe-Account` header
        $this->fakeStripe->queueJsonResponse(200, [
            'object' => 'list',
            'data' => [
                [
                    'id' => 'prod_VitD3',
                    'object' => 'product',
                    'name' => 'Vitamin D3',
                    'description' => 'Daily immune support',
                    'active' => true,
                    'default_price' => [
                        'id' => 'price_VitD3',
                        'object' => 'price',
                        'unit_amount' => 2499,
                        'currency' => 'usd',
                    ],
                ],
            ],
            'has_more' => false,
        ]);

        $this->get('/connect/storefront/acct_1StorefrontDemo')
            ->assertOk()
            ->assertSee('Sunset Pharmacy')
            ->assertSee('Vitamin D3')
            ->assertSee('24.99');

        $listProdReq = $this->fakeStripe->lastRequest();
        $this->assertSame('get', $listProdReq['method']);
        $this->assertStringEndsWith('/v1/products', $listProdReq['url']);
        $this->assertContains('Stripe-Account: acct_1StorefrontDemo', $listProdReq['headers']);
        $this->assertSame(20, $listProdReq['params']['limit']);
        $this->assertSame(['data.default_price'], $listProdReq['params']['expand']);

        // 3. Direct Charge Hosted Checkout with sample application_fee_amount = 123
        $this->fakeStripe->queueJsonResponse(200, [
            'id' => 'prod_VitD3',
            'object' => 'product',
            'name' => 'Vitamin D3',
            'description' => 'Daily immune support',
            'default_price' => [
                'id' => 'price_VitD3',
                'object' => 'price',
                'unit_amount' => 2499,
                'currency' => 'usd',
            ],
        ]);
        $this->fakeStripe->queueJsonResponse(200, [
            'id' => 'cs_test_direct_123',
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_direct_123',
        ]);

        $this->postJson('/connect/storefront/acct_1StorefrontDemo/checkout', [
            'product_id' => 'prod_VitD3',
            'quantity' => 2,
        ])
            ->assertOk()
            ->assertJson(['url' => 'https://checkout.stripe.com/c/pay/cs_test_direct_123']);

        $checkoutReq = $this->fakeStripe->lastRequest();
        $this->assertSame('post', $checkoutReq['method']);
        $this->assertStringEndsWith('/v1/checkout/sessions', $checkoutReq['url']);
        $this->assertContains('Stripe-Account: acct_1StorefrontDemo', $checkoutReq['headers']);
        $this->assertSame('payment', $checkoutReq['params']['mode']);
        $this->assertSame(123, $checkoutReq['params']['payment_intent_data']['application_fee_amount']);
        $this->assertSame(2499, $checkoutReq['params']['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame(2, $checkoutReq['params']['line_items'][0]['quantity']);
        $this->assertStringContainsString('session_id={CHECKOUT_SESSION_ID}', $checkoutReq['params']['success_url']);
    }

    public function test_platform_subscription_and_billing_portal_use_customer_account_not_customer(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $account = StripeConnectedAccount::create([
            'user_id' => $user->id,
            'stripe_account_id' => 'acct_1PlatformSub',
            'display_name' => 'Pro Pharmacy',
            'contact_email' => 'pro@acme.test',
        ]);

        // 1. Helpful error if STRIPE_SUBSCRIPTION_PRICE_ID is still a placeholder
        Config::set('stripe_connect.subscription_price_id', '<<<NEEDED:STRIPE_SUBSCRIPTION_PRICE_ID>>>');
        $this->actingAs($user)
            ->postJson("/connect/accounts/{$account->stripe_account_id}/subscribe")
            ->assertStatus(503)
            ->assertJsonFragment(['error' => 'Stripe Connect configuration error: STRIPE_SUBSCRIPTION_PRICE_ID is not configured. Create a recurring Price at the platform level in Stripe Dashboard (https://dashboard.stripe.com/products) and set STRIPE_SUBSCRIPTION_PRICE_ID=price_... in your .env file.']);

        // 2. Valid platform subscription Checkout Session using `customer_account`
        Config::set('stripe_connect.subscription_price_id', 'price_platform_pro_monthly');
        $this->fakeStripe->queueJsonResponse(200, [
            'id' => 'cs_test_sub_999',
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_sub_999',
        ]);

        $this->actingAs($user)
            ->postJson("/connect/accounts/{$account->stripe_account_id}/subscribe")
            ->assertOk()
            ->assertJson(['url' => 'https://checkout.stripe.com/c/pay/cs_test_sub_999']);

        $subReq = $this->fakeStripe->lastRequest();
        $this->assertSame('post', $subReq['method']);
        $this->assertStringEndsWith('/v1/checkout/sessions', $subReq['url']);
        $this->assertSame('acct_1PlatformSub', $subReq['params']['customer_account']);
        $this->assertArrayNotHasKey('customer', $subReq['params']);
        $this->assertSame('subscription', $subReq['params']['mode']);
        $this->assertSame('price_platform_pro_monthly', $subReq['params']['line_items'][0]['price']);
        // Must NOT send Stripe-Account header (platform-level subscription)
        foreach ($subReq['headers'] as $header) {
            $this->assertStringNotContainsString('Stripe-Account:', $header);
        }

        // 3. Billing Portal session using `customer_account`
        $this->fakeStripe->queueJsonResponse(200, [
            'id' => 'bps_test_123',
            'object' => 'billing_portal.session',
            'url' => 'https://billing.stripe.com/p/session/test_123',
        ]);

        $this->actingAs($user)
            ->postJson("/connect/accounts/{$account->stripe_account_id}/billing-portal")
            ->assertOk()
            ->assertJson(['url' => 'https://billing.stripe.com/p/session/test_123']);

        $portalReq = $this->fakeStripe->lastRequest();
        $this->assertSame('post', $portalReq['method']);
        $this->assertStringEndsWith('/v1/billing_portal/sessions', $portalReq['url']);
        $this->assertSame('acct_1PlatformSub', $portalReq['params']['customer_account']);
        $this->assertArrayNotHasKey('customer', $portalReq['params']);
    }

    public function test_v2_thin_webhook_verifies_signature_fetches_full_event_and_handles_all_account_event_types(): void
    {
        $account = StripeConnectedAccount::create([
            'stripe_account_id' => 'acct_1ThinHook',
            'display_name' => 'Thin Hook Pharmacy',
            'contact_email' => 'thin@acme.test',
        ]);

        $eventTypes = [
            'v2.core.account[requirements].updated' => 'last_requirements_event_id',
            'v2.core.account[configuration.merchant].capability_status_updated' => 'last_merchant_capability_event_id',
            'v2.core.account[configuration.customer].capability_status_updated' => 'last_customer_capability_event_id',
            'v2.core.account[configuration.recipient].capability_status_updated' => 'last_recipient_capability_event_id',
        ];

        $idx = 0;
        foreach ($eventTypes as $eventType => $metadataKey) {
            $idx++;
            $eventId = "evt_thin_{$idx}";

            $thinPayload = json_encode([
                'id' => $eventId,
                'object' => 'v2.core.event',
                'type' => $eventType,
                'created' => '2026-11-16T12:00:00.000Z',
                'livemode' => false,
                'context' => 'acct_1ThinHook',
                'related_object' => [
                    'id' => 'acct_1ThinHook',
                    'type' => 'v2.core.account',
                    'url' => '/v2/core/accounts/acct_1ThinHook',
                ],
            ]);

            // Queue response for `$stripeClient->v2->core->events->retrieve($eventId)`
            $fullEventBody = [
                'id' => $eventId,
                'object' => 'v2.core.event',
                'type' => $eventType,
                'created' => '2026-11-16T12:00:00.000Z',
                'context' => 'acct_1ThinHook',
                'related_object' => [
                    'id' => 'acct_1ThinHook',
                    'type' => 'v2.core.account',
                    'url' => '/v2/core/accounts/acct_1ThinHook',
                ],
            ];
            if (str_contains($eventType, 'capability_status_updated')) {
                $fullEventBody['data'] = [
                    'updated_capability' => 'card_payments',
                ];
            }
            $this->fakeStripe->queueJsonResponse(200, $fullEventBody);

            $sigHeader = $this->signStripeWebhookPayload($thinPayload, 'test_thin_webhook_secret_local');

            $this->call('POST', '/webhooks/stripe/thin', [], [], [], [
                'HTTP_STRIPE_SIGNATURE' => $sigHeader,
                'CONTENT_TYPE' => 'application/json',
            ], $thinPayload)
                ->assertOk()
                ->assertJson([
                    'received' => true,
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                ]);

            $fetchReq = $this->fakeStripe->lastRequest();
            $this->assertSame('get', $fetchReq['method']);
            $this->assertStringEndsWith("/v2/core/events/{$eventId}", $fetchReq['url']);
            $this->assertContains('Stripe-Context: acct_1ThinHook', $fetchReq['headers']);

            $account->refresh();
            $this->assertSame($eventId, $account->billing_metadata[$metadataKey] ?? null);
        }

        // Invalid signature must be rejected with HTTP 400.
        $this->call('POST', '/webhooks/stripe/thin', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't=12345,v1=badsignature',
            'CONTENT_TYPE' => 'application/json',
        ], '{"id":"evt_bad","object":"v2.core.event"}')
            ->assertStatus(400);
    }

    public function test_v1_snapshot_billing_webhooks_sync_subscriptions_payment_methods_tax_ids_and_portal(): void
    {
        $account = StripeConnectedAccount::create([
            'stripe_account_id' => 'acct_1BillingSync',
            'display_name' => 'Billing Sync Pharmacy',
            'contact_email' => 'billing@acme.test',
        ]);

        // 1. customer.subscription.updated (upgrade + quantity + scheduled pause)
        $this->sendBillingWebhook('customer.subscription.updated', [
            'id' => 'sub_123',
            'object' => 'subscription',
            'customer_account' => 'acct_1BillingSync',
            'status' => 'active',
            'cancel_at_period_end' => false,
            'default_payment_method' => 'pm_card_visa',
            'items' => [
                'data' => [
                    [
                        'price' => ['id' => 'price_tier_enterprise'],
                        'quantity' => 5,
                    ],
                ],
            ],
            'pause_collection' => [
                'behavior' => 'void',
                'resumes_at' => 1796083200,
            ],
        ])->assertOk();

        $account->refresh();
        $this->assertSame('sub_123', $account->stripe_subscription_id);
        $this->assertSame('active', $account->subscription_status);
        $this->assertSame('price_tier_enterprise', $account->subscription_price_id);
        $this->assertSame(5, $account->subscription_quantity);
        $this->assertSame('void', $account->pause_collection_behavior);
        $this->assertNotNull($account->pause_collection_resumes_at);
        $this->assertSame('pm_card_visa', $account->default_payment_method_id);

        // 2. customer.subscription.updated (resumed + cancel_at_period_end = true, then reactivated = false)
        $this->sendBillingWebhook('customer.subscription.updated', [
            'id' => 'sub_123',
            'object' => 'subscription',
            'customer_account' => 'acct_1BillingSync',
            'status' => 'active',
            'cancel_at_period_end' => true,
            'pause_collection' => null,
            'items' => ['data' => [['price' => ['id' => 'price_tier_enterprise'], 'quantity' => 5]]],
        ])->assertOk();

        $account->refresh();
        $this->assertTrue($account->cancel_at_period_end);
        $this->assertNull($account->pause_collection_behavior);

        // 3. invoice.paid
        $this->sendBillingWebhook('invoice.paid', [
            'id' => 'in_paid_001',
            'object' => 'invoice',
            'customer_account' => 'acct_1BillingSync',
            'subscription' => 'sub_123',
            'amount_paid' => 4900,
            'currency' => 'usd',
        ])->assertOk();

        $account->refresh();
        $this->assertSame('in_paid_001', $account->billing_metadata['last_paid_invoice_id'] ?? null);

        // 4. payment_method.attached & customer.updated & payment_method.detached
        $this->sendBillingWebhook('payment_method.attached', [
            'id' => 'pm_card_mastercard',
            'object' => 'payment_method',
            'customer_account' => 'acct_1BillingSync',
            'type' => 'card',
            'card' => ['brand' => 'mastercard', 'last4' => '4444'],
        ])->assertOk();

        $this->sendBillingWebhook('customer.updated', [
            'id' => 'cus_ignored',
            'object' => 'customer',
            'customer_account' => 'acct_1BillingSync',
            'email' => 'new-billing-only@acme.test',
            'invoice_settings' => [
                'default_payment_method' => 'pm_card_mastercard',
            ],
        ])->assertOk();

        $account->refresh();
        $this->assertSame('pm_card_mastercard', $account->default_payment_method_id);
        $this->assertSame('new-billing-only@acme.test', $account->billing_metadata['billing_email'] ?? null);

        $this->sendBillingWebhook('payment_method.detached', [
            'id' => 'pm_card_mastercard',
            'object' => 'payment_method',
            'customer_account' => 'acct_1BillingSync',
        ])->assertOk();

        $account->refresh();
        $this->assertNull($account->default_payment_method_id);

        // 5. customer.tax_id.created -> updated -> deleted
        $this->sendBillingWebhook('customer.tax_id.created', [
            'id' => 'txi_eu_001',
            'object' => 'tax_id',
            'customer_account' => 'acct_1BillingSync',
            'type' => 'eu_vat',
            'value' => 'DE123456789',
            'verification' => ['status' => 'pending'],
        ])->assertOk();

        $this->sendBillingWebhook('customer.tax_id.updated', [
            'id' => 'txi_eu_001',
            'object' => 'tax_id',
            'customer_account' => 'acct_1BillingSync',
            'type' => 'eu_vat',
            'value' => 'DE123456789',
            'verification' => ['status' => 'verified'],
        ])->assertOk();

        $account->refresh();
        $this->assertSame('verified', $account->tax_ids['txi_eu_001']['verification_status'] ?? null);

        $this->sendBillingWebhook('customer.tax_id.deleted', [
            'id' => 'txi_eu_001',
            'object' => 'tax_id',
            'customer_account' => 'acct_1BillingSync',
        ])->assertOk();

        $account->refresh();
        $this->assertArrayNotHasKey('txi_eu_001', $account->tax_ids ?? []);

        // 6. billing_portal.configuration.created / updated & billing_portal.session.created
        $this->sendBillingWebhook('billing_portal.configuration.created', [
            'id' => 'bpc_123',
            'object' => 'billing_portal.configuration',
            'active' => true,
        ])->assertOk();

        $this->sendBillingWebhook('billing_portal.configuration.updated', [
            'id' => 'bpc_123',
            'object' => 'billing_portal.configuration',
            'active' => true,
        ])->assertOk();

        $this->sendBillingWebhook('billing_portal.session.created', [
            'id' => 'bps_777',
            'object' => 'billing_portal.session',
            'customer_account' => 'acct_1BillingSync',
        ])->assertOk();

        $account->refresh();
        $this->assertSame('bps_777', $account->billing_metadata['last_billing_portal_session_id'] ?? null);

        // 7. customer.subscription.deleted revokes access
        $this->sendBillingWebhook('customer.subscription.deleted', [
            'id' => 'sub_123',
            'object' => 'subscription',
            'customer_account' => 'acct_1BillingSync',
            'status' => 'canceled',
        ])->assertOk();

        $account->refresh();
        $this->assertSame('canceled', $account->subscription_status);
        $this->assertFalse($account->hasActiveSubscription());
    }

    public function test_dashboard_renders_live_status_products_and_subscription_ui_and_enforces_ownership(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create(['role' => 'admin']);

        $account = StripeConnectedAccount::create([
            'user_id' => $owner->id,
            'stripe_account_id' => 'acct_1DashboardView',
            'display_name' => 'Beacon Pharmacy',
            'contact_email' => 'beacon@acme.test',
            'stripe_subscription_id' => 'sub_active_001',
            'subscription_status' => 'active',
            'subscription_price_id' => 'price_platform_pro_monthly',
            'subscription_quantity' => 2,
            'default_payment_method_id' => 'pm_visa_4242',
        ]);

        // Queue live V2 account retrieve + connected account product list for dashboard GET.
        $this->fakeStripe->queueJsonResponse(200, [
            'id' => 'acct_1DashboardView',
            'object' => 'v2.core.account',
            'display_name' => 'Beacon Pharmacy',
            'contact_email' => 'beacon@acme.test',
            'configuration' => [
                'merchant' => [
                    'applied' => true,
                    'capabilities' => [
                        'card_payments' => ['status' => 'active'],
                    ],
                ],
            ],
            'requirements' => [
                'summary' => [
                    'minimum_deadline' => ['status' => 'eventually_due'],
                ],
            ],
        ]);
        $this->fakeStripe->queueJsonResponse(200, [
            'object' => 'list',
            'data' => [
                [
                    'id' => 'prod_Omega3',
                    'object' => 'product',
                    'name' => 'Omega 3 Capsules',
                    'description' => 'Heart health formula',
                    'active' => true,
                    'default_price' => [
                        'id' => 'price_Omega3',
                        'object' => 'price',
                        'unit_amount' => 1500,
                        'currency' => 'usd',
                    ],
                ],
            ],
            'has_more' => false,
        ]);

        $this->actingAs($owner)
            ->get('/connect?accountId=acct_1DashboardView')
            ->assertOk()
            ->assertSee('Beacon Pharmacy')
            ->assertSee('Onboard to collect payments')
            ->assertSee('Active &amp; Ready', false)
            ->assertSee('Omega 3 Capsules')
            ->assertSee('15.00')
            ->assertSee('Subscribe to Platform Plan')
            ->assertSee('Manage Subscription (Billing Portal)')
            ->assertSee('price_platform_pro_monthly')
            ->assertSee('pm_visa_4242');

        // Another tenant user must NOT be able to onboard, create products, or open Billing Portal for owner's account.
        $this->actingAs($otherUser)
            ->postJson("/connect/accounts/{$account->stripe_account_id}/onboard")
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->postJson("/connect/accounts/{$account->stripe_account_id}/billing-portal")
            ->assertNotFound();
    }

    public function test_checkout_success_and_subscription_success_pages_render_cleanly(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        StripeConnectedAccount::create([
            'user_id' => $user->id,
            'stripe_account_id' => 'acct_1SuccessPages',
            'display_name' => 'Clover Pharmacy',
            'contact_email' => 'clover@acme.test',
        ]);

        // Direct Charge checkout success page retrieves Checkout Session using `Stripe-Account` header.
        $this->fakeStripe->queueJsonResponse(200, [
            'id' => 'cs_test_paid_555',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'amount_total' => 2499,
            'currency' => 'usd',
        ]);

        $this->get('/connect/storefront/acct_1SuccessPages/success?session_id=cs_test_paid_555')
            ->assertOk()
            ->assertSee('Thank you for your purchase!')
            ->assertSee('cs_test_paid_555')
            ->assertSee('24.99')
            ->assertSee('USD');

        $sessionReq = $this->fakeStripe->lastRequest();
        $this->assertSame('get', $sessionReq['method']);
        $this->assertStringEndsWith('/v1/checkout/sessions/cs_test_paid_555', $sessionReq['url']);
        $this->assertContains('Stripe-Account: acct_1SuccessPages', $sessionReq['headers']);

        // Platform subscription success page
        $this->actingAs($user)
            ->get('/connect/accounts/acct_1SuccessPages/subscription/success?session_id=cs_sub_777')
            ->assertOk()
            ->assertSee('Platform subscription started')
            ->assertSee('cs_sub_777');
    }

    /**
     * Send a signed V1 snapshot event to `/webhooks/stripe/billing`.
     *
     * @param  array<string, mixed>  $objectData
     */
    private function sendBillingWebhook(string $type, array $objectData)
    {
        $payload = json_encode([
            'id' => 'evt_'.str_replace('.', '_', $type).'_'.uniqid(),
            'object' => 'event',
            'type' => $type,
            'data' => [
                'object' => $objectData,
            ],
        ]);

        $sigHeader = $this->signStripeWebhookPayload((string) $payload, 'test_billing_webhook_secret_local');

        return $this->call('POST', '/webhooks/stripe/billing', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], (string) $payload);
    }

    private function signStripeWebhookPayload(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return "t={$timestamp},v1={$signature}";
    }
}

/**
 * In-memory recording Stripe HttpClient used to verify real `stripe/stripe-php`
 * SDK requests (headers, URLs, parameters, and API version) without network I/O.
 */
final class FakeStripeHttpClient implements ClientInterface
{
    /** @var array<int, array{status: int, body: string, headers: array<string, string>}> */
    private array $queue = [];

    /** @var array<int, array{method: string, url: string, headers: array<int, string>, params: array<string, mixed>, apiMode: string}> */
    public array $requests = [];

    /**
     * @param  array<string, mixed>  $body
     */
    public function queueJsonResponse(int $status, array $body): void
    {
        $this->queue[] = [
            'status' => $status,
            'body' => (string) json_encode($body),
            'headers' => ['content-type' => 'application/json'],
        ];
    }

    /**
     * @return array{method: string, url: string, headers: array<int, string>, params: array<string, mixed>, apiMode: string}
     */
    public function lastRequest(): array
    {
        return $this->requests[array_key_last($this->requests)];
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $this->requests[] = [
            'method' => $method,
            'url' => $absUrl,
            'headers' => $headers,
            'params' => is_array($params) ? $params : [],
            'apiMode' => $apiMode,
        ];

        $next = array_shift($this->queue);
        if ($next === null) {
            throw new \RuntimeException("FakeStripeHttpClient had no queued response for {$method} {$absUrl}");
        }

        return [$next['body'], $next['status'], $next['headers']];
    }
}
