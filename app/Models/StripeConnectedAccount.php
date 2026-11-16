<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps a Z-Syst user (and optional business tenant) to a Stripe V2 Connected Account
 * (`acct_...`), and stores platform-level subscription & billing state kept up to date
 * by Stripe's V1 snapshot webhooks.
 *
 * Important Design Notes:
 *   1. With Stripe V2 Accounts, `stripe_account_id` serves as BOTH the connected
 *      account ID (`Stripe-Account` header) and the platform customer identifier
 *      (`customer_account` on Checkout Sessions, Billing Portal, Subscriptions, etc.).
 *      Do NOT store or use legacy V1 `cus_...` customer IDs for V2 accounts.
 *   2. Onboarding status (`ready_to_process_payments`, `onboarding_complete`,
 *      `requirements`) is intentionally NOT stored in this model — always query the
 *      V2 Accounts API directly via StripeConnectService::getAccountOnboardingStatus().
 *   3. This model intentionally does NOT use TenantScoped because public storefronts
 *      and incoming Stripe webhooks look up records cross-tenant by `stripe_account_id`.
 *      Dashboard routes enforce per-user ownership explicitly.
 */
class StripeConnectedAccount extends Model
{
    protected $table = 'stripe_connected_accounts';

    protected $fillable = [
        'user_id',
        'business_id',
        'stripe_account_id',
        'display_name',
        'contact_email',
        'stripe_subscription_id',
        'subscription_status',
        'subscription_price_id',
        'subscription_quantity',
        'cancel_at_period_end',
        'pause_collection_behavior',
        'pause_collection_resumes_at',
        'default_payment_method_id',
        'tax_ids',
        'billing_metadata',
    ];

    protected $casts = [
        'business_id' => 'integer',
        'subscription_quantity' => 'integer',
        'cancel_at_period_end' => 'boolean',
        'pause_collection_resumes_at' => 'datetime',
        'tax_ids' => 'array',
        'billing_metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Find a connected account mapping by its Stripe V2 Account ID (`acct_...`).
     */
    public static function findByStripeAccountId(string $stripeAccountId): ?self
    {
        return static::query()->where('stripe_account_id', $stripeAccountId)->first();
    }

    /**
     * Whether this connected account currently holds an active or trialing platform subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return in_array($this->subscription_status, ['active', 'trialing'], true);
    }

    /**
     * Whether subscription invoice collection is currently paused (e.g. via Customer Portal).
     */
    public function isSubscriptionPaused(): bool
    {
        return ! empty($this->pause_collection_behavior);
    }
}
