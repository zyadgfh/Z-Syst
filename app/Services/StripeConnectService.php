<?php

namespace App\Services;

use App\Exceptions\StripeConnectConfigurationException;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Event as V1Event;
use Stripe\Product;
use Stripe\StripeClient;
use Stripe\StripeContext;
use Stripe\V2\Core\Account as V2Account;
use Stripe\V2\Core\AccountLink as V2AccountLink;
use Stripe\V2\Core\Event as V2Event;
use Stripe\V2\Core\EventNotification;
use Stripe\Webhook;

/**
 * Core Stripe Connect service encapsulating every Stripe API interaction.
 *
 * Key Architectural Guarantees:
 *   1. Uses a single `Stripe\StripeClient` instance for ALL Stripe API requests
 *      (never global `Stripe::setApiKey()` or legacy static model calls).
 *   2. Uses the latest Stripe PHP SDK (`stripe/stripe-php` v23.0.0) without
 *      overriding `stripe_version` — the SDK automatically pins the latest API
 *      version (`2026-10-28.endive`).
 *   3. Creates Connected Accounts using the V2 Accounts API ONLY with the
 *      explicit V2 properties (never top-level `type: 'express'|'standard'|'custom'`).
 *   4. Always fetches onboarding status directly from the V2 Accounts API (never
 *      persisted/cached in a database).
 *   5. Uses the `stripe_account` request option (`Stripe-Account` header) for
 *      connected-account products and Direct Charges, and `customer_account`
 *      (never `.customer`) for platform-level V2 subscriptions and Billing Portal.
 *   6. Validates every required configuration placeholder and raises a helpful
 *      StripeConnectConfigurationException when a value is missing.
 */
class StripeConnectService
{
    private ?StripeClient $client = null;

    public function __construct(?StripeClient $client = null)
    {
        $this->client = $client;
    }

    /**
     * Allow injecting a pre-configured StripeClient (useful for testing).
     */
    public function setClient(?StripeClient $client): void
    {
        $this->client = $client;
    }

    /**
     * Return (and lazily instantiate) the single StripeClient used for all
     * Stripe API requests.
     *
     * @throws StripeConnectConfigurationException if STRIPE_SECRET_KEY is missing or still a placeholder
     */
    public function stripeClient(): StripeClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        // PLACEHOLDER CHECK: Ensure the developer replaced STRIPE_SECRET_KEY in `.env`.
        $secretKey = $this->requireConfigValue(
            'stripe_connect.secret_key',
            'STRIPE_SECRET_KEY',
            'Add your Stripe platform secret key (e.g. sk_test_... from https://dashboard.stripe.com/apikeys) to your .env file as STRIPE_SECRET_KEY.'
        );

        // Create the StripeClient using ONLY `api_key`.
        // Do NOT pass `stripe_version`: stripe/stripe-php v23.0.0 automatically
        // sends API version `2026-10-28.endive` on every request.
        $this->client = new StripeClient([
            'api_key' => $secretKey,
        ]);

        return $this->client;
    }

    /**
     * Inspect configuration status for the Connect Dashboard setup checklist
     * without throwing exceptions, so the UI can clearly guide the developer.
     *
     * @return array<string, array{env: string, configured: bool, label: string, hint: string}>
     */
    public function configurationChecklist(): array
    {
        return [
            'secret_key' => [
                'env' => 'STRIPE_SECRET_KEY',
                'configured' => $this->isConfiguredValue(config('stripe_connect.secret_key')),
                'label' => 'Platform Secret API Key',
                'hint' => 'Copy your secret key (sk_test_...) from Stripe Dashboard → Developers → API keys.',
            ],
            'thin_webhook_secret' => [
                'env' => 'STRIPE_THIN_WEBHOOK_SECRET',
                'configured' => $this->isConfiguredValue(config('stripe_connect.thin_webhook_secret')),
                'label' => 'V2 Thin Event Destination Secret',
                'hint' => 'Create a Thin Event Destination for Connected accounts (or run `stripe listen --thin-events ...`) and set whsec_... here.',
            ],
            'webhook_secret' => [
                'env' => 'STRIPE_WEBHOOK_SECRET',
                'configured' => $this->isConfiguredValue(config('stripe_connect.webhook_secret')),
                'label' => 'V1 Snapshot Webhook Secret (Subscriptions)',
                'hint' => 'Create a standard snapshot webhook endpoint pointing to /webhooks/stripe/billing and set whsec_... here.',
            ],
            'subscription_price_id' => [
                'env' => 'STRIPE_SUBSCRIPTION_PRICE_ID',
                'configured' => $this->isConfiguredValue(config('stripe_connect.subscription_price_id')),
                'label' => 'Platform Subscription Price ID',
                'hint' => 'Create a recurring product/price at the platform level in Stripe Dashboard and set its price_... ID here.',
            ],
        ];
    }

    /**
     * Validate that a config value is present and not an unfilled placeholder.
     *
     * @throws StripeConnectConfigurationException
     */
    public function requireConfigValue(string $configKey, string $envName, string $guidance): string
    {
        $value = config($configKey);

        if (! $this->isConfiguredValue($value)) {
            throw new StripeConnectConfigurationException($envName, $guidance);
        }

        return trim((string) $value);
    }

    /**
     * Detect empty values or common placeholder tokens (`<<<NEEDED:...>>>`,
     * `sk_***`, `whsec_***`, `price_***`, `YOUR_...`, `REPLACE_...`, etc.).
     */
    public function isConfiguredValue(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }

        $upper = strtoupper($trimmed);

        foreach (['<<<NEEDED:', 'PLACEHOLDER', 'REPLACE_WITH', 'YOUR_', 'CHANGE_ME', '***'] as $marker) {
            if (str_contains($upper, $marker)) {
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // 1. CREATING CONNECTED ACCOUNTS (Stripe V2 Accounts API)
    // =========================================================================

    /**
     * Create a new Connected Account using Stripe's V2 Core Accounts API.
     *
     * IMPORTANT:
     *   - Uses ONLY the specified V2 properties below.
     *   - NEVER passes a top-level `type` (`express`, `standard`, or `custom`).
     *   - Configures BOTH `customer` and `merchant` configurations so the single
     *     V2 Account ID (`acct_...`) can both sell products on its storefront AND
     *     be billed for a platform subscription via `customer_account`.
     *
     * @see https://docs.stripe.com/api/v2/core/accounts/object
     */
    public function createConnectedAccount(string $displayName, string $contactEmail): V2Account
    {
        return $this->stripeClient()->v2->core->accounts->create([
            'display_name' => $displayName,
            'contact_email' => $contactEmail,
            'identity' => [
                'country' => 'us',
            ],
            'dashboard' => 'full',
            'defaults' => [
                'responsibilities' => [
                    'fees_collector' => 'stripe',
                    'losses_collector' => 'stripe',
                ],
            ],
            'configuration' => [
                // IMPORTANT (PHP JSON encoding): Pass `new \stdClass()` instead of `[]`
                // so `json_encode` serializes `"customer": {}` (JSON object) rather than
                // `"customer": []` (JSON array), as required by Stripe's V2 JSON API.
                'customer' => new \stdClass(),
                'merchant' => [
                    'capabilities' => [
                        'card_payments' => [
                            'requested' => true,
                        ],
                    ],
                ],
            ],
        ]);
    }

    // =========================================================================
    // 2. ONBOARDING CONNECTED ACCOUNTS (V2 Account Links + Live API Status)
    // =========================================================================

    /**
     * Create a single-use Stripe-hosted Account Link so the connected account
     * owner can complete identity verification and payout setup.
     *
     * Both `merchant` and `customer` configurations are included in onboarding.
     */
    public function createOnboardingAccountLink(string $accountId, string $refreshUrl, string $returnUrl): V2AccountLink
    {
        return $this->stripeClient()->v2->core->accountLinks->create([
            'account' => $accountId,
            'use_case' => [
                'type' => 'account_onboarding',
                'account_onboarding' => [
                    'configurations' => ['merchant', 'customer'],
                    'refresh_url' => $refreshUrl,
                    'return_url' => $returnUrl,
                ],
            ],
        ]);
    }

    /**
     * Retrieve the live onboarding and capability status directly from Stripe's
     * V2 Accounts API.
     *
     * IMPORTANT: For this integration, we ALWAYS call Stripe's Accounts API
     * directly rather than storing or caching onboarding state in the database.
     *
     * @return array{
     *   account_id: string,
     *   display_name: ?string,
     *   contact_email: ?string,
     *   card_payments_status: ?string,
     *   ready_to_process_payments: bool,
     *   requirements_status: ?string,
     *   onboarding_complete: bool,
     *   requirements_entries: array<int, array{description: string, deadline_status: ?string, awaiting_action_from: ?string}>,
     *   account: V2Account
     * }
     */
    public function getAccountOnboardingStatus(string $accountId): array
    {
        // Include `configuration.merchant` (for card_payments capability status)
        // and `requirements` (for minimum_deadline status and pending items).
        $account = $this->stripeClient()->v2->core->accounts->retrieve($accountId, [
            'include' => ['configuration.merchant', 'requirements'],
        ]);

        // Determine if the merchant's card_payments capability is active.
        $cardPaymentsStatus = $account->configuration?->merchant?->capabilities?->card_payments?->status ?? null;
        $readyToProcessPayments = $cardPaymentsStatus === 'active';

        // Determine onboarding completion from requirements summary deadline status.
        $requirementsStatus = $account->requirements?->summary?->minimum_deadline?->status ?? null;
        $onboardingComplete = $requirementsStatus !== 'currently_due' && $requirementsStatus !== 'past_due';

        // Extract human-readable pending requirement entries (if any) for UI display.
        $entries = [];
        $rawEntries = $account->requirements?->entries ?? [];
        if (is_iterable($rawEntries)) {
            foreach ($rawEntries as $entry) {
                $entries[] = [
                    'description' => (string) ($entry->description ?? 'Additional verification required'),
                    'deadline_status' => $entry->minimum_deadline?->status ?? null,
                    'awaiting_action_from' => $entry->awaiting_action_from ?? null,
                ];
            }
        }

        return [
            'account_id' => (string) ($account->id ?? $accountId),
            'display_name' => $account->display_name ?? null,
            'contact_email' => $account->contact_email ?? null,
            'card_payments_status' => $cardPaymentsStatus,
            'ready_to_process_payments' => $readyToProcessPayments,
            'requirements_status' => $requirementsStatus,
            'onboarding_complete' => $onboardingComplete,
            'requirements_entries' => $entries,
            'account' => $account,
        ];
    }

    // =========================================================================
    // 3. PRODUCTS ON CONNECTED ACCOUNT (`Stripe-Account` Header)
    // =========================================================================

    /**
     * Create a Stripe Product (with an inline default Price) directly on the
     * connected account by passing `['stripe_account' => $accountId]` as the
     * request options (which sets the `Stripe-Account` HTTP header).
     */
    public function createProduct(
        string $accountId,
        string $name,
        ?string $description,
        int $priceInCents,
        string $currency = 'usd',
    ): Product {
        $params = [
            'name' => $name,
            'default_price_data' => [
                'unit_amount' => $priceInCents,
                'currency' => strtolower($currency),
            ],
        ];

        if ($description !== null && trim($description) !== '') {
            $params['description'] = trim($description);
        }

        // Passing `stripe_account` in the 2nd argument sends the `Stripe-Account` header.
        return $this->stripeClient()->products->create($params, [
            'stripe_account' => $accountId,
        ]);
    }

    /**
     * List active Stripe products belonging to a connected account, expanding
     * `data.default_price` so the storefront can render prices in a single API call.
     *
     * @return array<int, Product>
     */
    public function listProducts(string $accountId, int $limit = 20): array
    {
        // In stripe-php, listing uses `->all($params, $opts)` with `stripe_account`.
        $collection = $this->stripeClient()->products->all([
            'limit' => $limit,
            'active' => true,
            'expand' => ['data.default_price'],
        ], [
            'stripe_account' => $accountId,
        ]);

        return is_array($collection->data) ? $collection->data : iterator_to_array($collection->data);
    }

    /**
     * Retrieve a single product from a connected account (with `default_price`
     * expanded) so checkout always prices items from Stripe rather than trusting
     * browser-supplied amounts.
     */
    public function retrieveProduct(string $accountId, string $productId): Product
    {
        return $this->stripeClient()->products->retrieve($productId, [
            'expand' => ['default_price'],
        ], [
            'stripe_account' => $accountId,
        ]);
    }

    // =========================================================================
    // 4. DIRECT CHARGE CHECKOUT WITH APPLICATION FEE (Storefront Purchase)
    // =========================================================================

    /**
     * Create a Stripe Hosted Checkout Session for a Direct Charge on the
     * connected account, collecting a platform `application_fee_amount`.
     *
     * Flow:
     *   1. Retrieve the product + default_price from the connected account so
     *      the unit amount and currency are verified server-side.
     *   2. Compute the platform application fee (defaults to 123 cents from
     *      `STRIPE_APPLICATION_FEE_AMOUNT`, safely capped below the total).
     *   3. Create a Checkout Session with `mode: 'payment'`, `price_data`, and
     *      `payment_intent_data.application_fee_amount`, passing
     *      `['stripe_account' => $accountId]` to execute a Direct Charge.
     */
    public function createDirectChargeCheckoutSession(
        string $accountId,
        string $productId,
        int $quantity,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSession {
        $product = $this->retrieveProduct($accountId, $productId);
        $defaultPrice = $product->default_price;

        $unitAmount = is_object($defaultPrice) ? ($defaultPrice->unit_amount ?? null) : null;
        $currency = is_object($defaultPrice) ? ($defaultPrice->currency ?? null) : null;

        if (! is_int($unitAmount) || $unitAmount <= 0 || ! is_string($currency) || $currency === '') {
            throw new \InvalidArgumentException(
                "Product [{$productId}] on connected account [{$accountId}] does not have a valid fixed default_price."
            );
        }

        $totalAmount = $unitAmount * $quantity;
        $configuredFee = max(0, (int) config('stripe_connect.application_fee_amount', 123));

        // Stripe requires application_fee_amount to be strictly less than the total charge.
        // If a demo product is cheaper than the default 123-cent fee, cap it safely.
        $applicationFeeAmount = $totalAmount > 1
            ? min($configuredFee, $totalAmount - 1)
            : 0;

        $productData = [
            'name' => (string) $product->name,
        ];
        if (! empty($product->description)) {
            $productData['description'] = (string) $product->description;
        }

        $params = [
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'unit_amount' => $unitAmount,
                        'product_data' => $productData,
                    ],
                    'quantity' => $quantity,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ];

        if ($applicationFeeAmount > 0) {
            $params['payment_intent_data'] = [
                // Sample Platform Application Fee collected from this Direct Charge
                'application_fee_amount' => $applicationFeeAmount,
            ];
        }

        // Direct Charge: pass `stripe_account` in request options (`Stripe-Account` header).
        return $this->stripeClient()->checkout->sessions->create($params, [
            'stripe_account' => $accountId,
        ]);
    }

    /**
     * Retrieve a Direct Charge Checkout Session from the connected account so
     * the success page can render an order confirmation summary.
     */
    public function retrieveDirectChargeCheckoutSession(string $accountId, string $sessionId): CheckoutSession
    {
        return $this->stripeClient()->checkout->sessions->retrieve($sessionId, [], [
            'stripe_account' => $accountId,
        ]);
    }

    // =========================================================================
    // 5. PLATFORM SUBSCRIPTION & BILLING PORTAL (V2 `customer_account`)
    // =========================================================================

    /**
     * Create a platform-level Hosted Checkout Session to charge a recurring
     * SaaS subscription to a V2 Connected Account.
     *
     * IMPORTANT:
     *   - With V2 Accounts, pass `customer_account` (the `acct_...` ID), NOT `customer`.
     *   - Do NOT pass `stripe_account` in request options: this subscription is
     *     created on YOUR PLATFORM account to bill the connected account.
     *
     * @throws StripeConnectConfigurationException if STRIPE_SUBSCRIPTION_PRICE_ID is missing or a placeholder
     */
    public function createPlatformSubscriptionCheckoutSession(
        string $accountId,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSession {
        // PLACEHOLDER CHECK: Developer must create a recurring Price at the platform
        // level in Stripe Dashboard and set STRIPE_SUBSCRIPTION_PRICE_ID=price_... in .env.
        $priceId = $this->requireConfigValue(
            'stripe_connect.subscription_price_id',
            'STRIPE_SUBSCRIPTION_PRICE_ID',
            'Create a recurring Price at the platform level in Stripe Dashboard (https://dashboard.stripe.com/products) and set STRIPE_SUBSCRIPTION_PRICE_ID=price_... in your .env file.'
        );

        return $this->stripeClient()->checkout->sessions->create([
            // V2 Account ID (`acct_...`) acts as the platform customer.
            'customer_account' => $accountId,
            'mode' => 'subscription',
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }

    /**
     * Create a Stripe Customer Billing Portal session so the connected account
     * can upgrade, downgrade, update quantity, pause, cancel, reactivate, or
     * manage payment methods and tax IDs for their platform subscription.
     *
     * Uses `customer_account` (the V2 `acct_...` ID) — never `customer`.
     */
    public function createBillingPortalSession(string $accountId, string $returnUrl): BillingPortalSession
    {
        return $this->stripeClient()->billingPortal->sessions->create([
            'customer_account' => $accountId,
            'return_url' => $returnUrl,
        ]);
    }

    // =========================================================================
    // 6. WEBHOOK PARSING (V2 Thin Events + V1 Snapshot Events)
    // =========================================================================

    /**
     * Verify and parse a V2 **Thin** Event Notification delivered to
     * `/webhooks/stripe/thin`.
     *
     * SDK Note (`stripe/stripe-php` v23.0.0):
     *   In PHP SDK v23+, thin event notifications are verified and parsed with
     *   `$stripeClient->parseEventNotification($payload, $sigHeader, $secret)`
     *   (the PHP equivalent of `parseThinEvent()` in earlier / JS SDK versions).
     *
     * @throws StripeConnectConfigurationException if STRIPE_THIN_WEBHOOK_SECRET is missing or a placeholder
     */
    public function parseThinEventNotification(string $rawPayload, string $signatureHeader): EventNotification
    {
        // PLACEHOLDER CHECK: Ensure STRIPE_THIN_WEBHOOK_SECRET is configured in .env.
        $thinWebhookSecret = $this->requireConfigValue(
            'stripe_connect.thin_webhook_secret',
            'STRIPE_THIN_WEBHOOK_SECRET',
            'Create a Thin Event Destination for Connected accounts in Stripe Dashboard (Developers → Webhooks → + Add destination → Connected accounts → Thin) or run `stripe listen --thin-events ...`, then set STRIPE_THIN_WEBHOOK_SECRET=whsec_... in your .env file.'
        );

        return $this->stripeClient()->parseEventNotification(
            $rawPayload,
            $signatureHeader,
            $thinWebhookSecret
        );
    }

    /**
     * Fetch the full V2 Event object from Stripe's V2 Events API after parsing
     * a thin event notification.
     */
    public function retrieveV2Event(string $eventId, ?StripeContext $context = null): V2Event
    {
        $opts = [];
        if ($context !== null) {
            $opts['stripe_context'] = $context;
        }

        return $this->stripeClient()->v2->core->events->retrieve($eventId, [], $opts);
    }

    /**
     * Verify and construct a standard V1 **Snapshot** webhook Event delivered
     * to `/webhooks/stripe/billing` (for subscriptions, invoices, payment
     * methods, customer updates, tax IDs, and billing portal events).
     *
     * Note: These subscription/billing webhooks use standard snapshot payloads,
     * NOT thin events.
     *
     * @throws StripeConnectConfigurationException if STRIPE_WEBHOOK_SECRET is missing or a placeholder
     */
    public function constructSnapshotWebhookEvent(string $rawPayload, string $signatureHeader): V1Event
    {
        // PLACEHOLDER CHECK: Ensure STRIPE_WEBHOOK_SECRET is configured in .env.
        $webhookSecret = $this->requireConfigValue(
            'stripe_connect.webhook_secret',
            'STRIPE_WEBHOOK_SECRET',
            'Create a standard snapshot webhook endpoint pointing to /webhooks/stripe/billing in Stripe Dashboard (or run `stripe listen --forward-to ...`), then set STRIPE_WEBHOOK_SECRET=whsec_... in your .env file.'
        );

        return Webhook::constructEvent($rawPayload, $signatureHeader, $webhookSecret);
    }
}
