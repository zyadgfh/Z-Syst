<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the `stripe_connected_accounts` table.
     *
     * What is stored here:
     *   1. The mapping between a Z-Syst user (`app_users.id` UUID) — and optionally
     *      their tenant (`businesses.id`) — and their Stripe V2 Connected Account ID
     *      (`acct_...`). With Stripe V2 Accounts, this single ID serves as BOTH the
     *      connected account and the customer (`customer_account`) for platform billing.
     *   2. Platform subscription and billing metadata synchronized asynchronously from
     *      Stripe's V1 *snapshot* webhooks (`/webhooks/stripe/billing`).
     *
     * What is DELIBERATELY NOT stored here:
     *   Onboarding completion state, capability statuses (`card_payments`), and
     *   active requirements are NOT cached in this table. For this integration,
     *   account onboarding status is ALWAYS queried live from Stripe's V2 Accounts
     *   API (`$stripeClient->v2->core->accounts->retrieve(...)`).
     */
    public function up(): void
    {
        Schema::create('stripe_connected_accounts', function (Blueprint $table) {
            $table->id();

            // Maps the Z-Syst user object (UUID PK on `app_users`) to the Stripe account ID.
            // Nullable so standalone sample/demo sessions work without requiring GoTrue login.
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('app_users')
                ->nullOnDelete();

            // Optional link to the user's Z-Syst pharmacy/business tenant.
            $table->foreignId('business_id')
                ->nullable()
                ->constrained('businesses')
                ->nullOnDelete();

            // Stripe V2 Account ID (format: acct_...). Also passed as `customer_account`
            // for platform subscriptions and Billing Portal sessions.
            $table->string('stripe_account_id')->unique();

            // Basic profile fields provided when creating the V2 account.
            $table->string('display_name');
            $table->string('contact_email');

            // -----------------------------------------------------------------
            // Platform Subscription & Billing State (synced via V1 webhooks)
            // -----------------------------------------------------------------
            // Updated by /webhooks/stripe/billing for:
            //   - customer.subscription.created / updated / deleted
            //   - invoice.paid
            //   - payment_method.attached / detached
            //   - customer.updated
            //   - customer.tax_id.created / updated / deleted
            //   - billing_portal.session.created / configuration.created / updated
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->string('subscription_status')->nullable(); // active, trialing, past_due, canceled, incomplete, unpaid
            $table->string('subscription_price_id')->nullable(); // current Price ID (tracks upgrades & downgrades)
            $table->integer('subscription_quantity')->nullable(); // current item quantity
            $table->boolean('cancel_at_period_end')->default(false); // true when cancellation is scheduled; false if reactivated
            $table->string('pause_collection_behavior')->nullable(); // e.g. 'void' when paused via Customer Portal
            $table->timestamp('pause_collection_resumes_at')->nullable(); // scheduled resume time if collection is paused
            $table->string('default_payment_method_id')->nullable(); // updated from customer.updated / subscription / payment_method events
            $table->json('tax_ids')->nullable(); // tracked customer tax IDs & validation statuses
            $table->json('billing_metadata')->nullable(); // latest paid invoice, attached PMs, portal sessions

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_connected_accounts');
    }
};
