<?php

/*
|--------------------------------------------------------------------------
| Stripe Connect Integration Configuration (V2 Accounts + Direct Charges)
|--------------------------------------------------------------------------
|
| This configuration powers the sample Stripe Connect integration using the
| latest Stripe PHP SDK (stripe/stripe-php v23.0.0).
|
| API Version Note:
|   The latest Stripe API version is `2026-10-28.endive`, which is pinned
|   automatically inside the SDK (\Stripe\Util\ApiVersion::CURRENT). Do NOT
|   pass a manual `stripe_version` option when constructing StripeClient.
|
| Placeholders:
|   Values marked `<<<NEEDED:...>>>` in `.env.example` MUST be replaced with
|   your real Stripe test/live credentials in `.env`. If any required value is
|   missing or still contains a placeholder, StripeConnectService throws a
|   helpful StripeConnectConfigurationException naming the exact env variable.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Stripe API Keys
    |--------------------------------------------------------------------------
    |
    | PLACEHOLDER: Set STRIPE_SECRET_KEY in your `.env` file to your platform's
    | secret API key from https://dashboard.stripe.com/apikeys
    | (e.g. sk_test_... for sandbox/testing or sk_live_... for production).
    |
    */
    'secret_key' => env('STRIPE_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Publishable Key (Optional — reserved for client-side Stripe.js flows)
    |--------------------------------------------------------------------------
    |
    | PLACEHOLDER: Set STRIPE_PUBLISHABLE_KEY in your `.env` file if you later
    | add embedded Stripe.js components (e.g. pk_test_... / pk_live_...).
    |
    */
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | V2 Thin Event Destination Signing Secret (Connected Account Updates)
    |--------------------------------------------------------------------------
    |
    | PLACEHOLDER: Set STRIPE_THIN_WEBHOOK_SECRET in `.env` to the signing
    | secret (whsec_...) of your V2 *Thin* Event Destination listening to
    | Connected accounts events:
    |   - v2.core.account[requirements].updated
    |   - v2.core.account[configuration.merchant].capability_status_updated
    |   - v2.core.account[configuration.customer].capability_status_updated
    |   - v2.core.account[configuration.recipient].capability_status_updated
    |
    | Local development via Stripe CLI:
    |   stripe listen \
    |     --thin-events 'v2.core.account[requirements].updated,v2.core.account[.recipient].capability_status_updated,v2.core.account[configuration.merchant].capability_status_updated,v2.core.account[configuration.customer].capability_status_updated' \
    |     --forward-thin-to http://localhost:8000/webhooks/stripe/thin
    |
    */
    'thin_webhook_secret' => env('STRIPE_THIN_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | V1 Snapshot Webhook Signing Secret (Subscriptions & Billing Portal)
    |--------------------------------------------------------------------------
    |
    | PLACEHOLDER: Set STRIPE_WEBHOOK_SECRET in `.env` to the signing secret
    | (whsec_...) of your standard V1 *Snapshot* Webhook endpoint (NOT thin)
    | listening to platform subscription, invoice, payment method, customer,
    | tax ID, and billing portal events at:
    |   POST /webhooks/stripe/billing
    |
    | Local development via Stripe CLI:
    |   stripe listen \
    |     --events customer.subscription.created,customer.subscription.updated,customer.subscription.deleted,invoice.paid,payment_method.attached,payment_method.detached,customer.updated,customer.tax_id.created,customer.tax_id.updated,customer.tax_id.deleted,billing_portal.configuration.created,billing_portal.configuration.updated,billing_portal.session.created \
    |     --forward-to http://localhost:8000/webhooks/stripe/billing
    |
    */
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Platform SaaS Subscription Price ID (Charged to Connected Accounts)
    |--------------------------------------------------------------------------
    |
    | PLACEHOLDER: Create a recurring Product & Price at the *platform* level
    | in your Stripe Dashboard (https://dashboard.stripe.com/products) and put
    | its Price ID (e.g. price_1234...) into STRIPE_SUBSCRIPTION_PRICE_ID.
    |
    | With Stripe V2 Accounts, a single Account ID (acct_...) acts as BOTH the
    | connected account and the platform customer (`customer_account`).
    |
    */
    'subscription_price_id' => env('STRIPE_SUBSCRIPTION_PRICE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Direct Charge Application Fee (in smallest currency unit, e.g. cents)
    |--------------------------------------------------------------------------
    |
    | Platform monetization fee collected via `payment_intent_data.application_fee_amount`
    | on Direct Charges created for a connected account's storefront checkout.
    | Defaults to 123 cents ($1.23) as a sample fee; automatically capped below
    | the checkout total if a sample item costs less than this fee.
    |
    */
    'application_fee_amount' => (int) env('STRIPE_APPLICATION_FEE_AMOUNT', 123),

    /*
    |--------------------------------------------------------------------------
    | Default Currency for New Connected Account Products
    |--------------------------------------------------------------------------
    */
    'default_currency' => strtolower((string) env('STRIPE_DEFAULT_CURRENCY', 'usd')),

];
