<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\StripeConnectConfigurationException;
use App\Http\Controllers\Controller;
use App\Models\StripeConnectedAccount;
use App\Services\StripeConnectService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Event as V1Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\V2\Core\Event as V2Event;
use Stripe\V2\Core\EventNotification;

/**
 * Stripe Webhook Controller handling BOTH webhook styles required by Connect V2:
 *
 *   1. `POST /webhooks/stripe/thin` — **V2 Thin Event Destination**
 *      Receives lightweight V2 event notifications for connected account
 *      requirement & capability changes. Verified via
 *      `$stripeClient->parseEventNotification()` (stripe/stripe-php v23.0.0),
 *      then retrieves the full V2 event via `$stripeClient->v2->core->events->retrieve()`.
 *
 *      Stripe Dashboard Setup:
 *        Developers → Webhooks → + Add destination
 *        → Events from: "Connected accounts"
 *        → Show advanced options → Payload style: "Thin"
 *        → Events: search "v2" and select:
 *            - v2.core.account[requirements].updated
 *            - v2.core.account[configuration.merchant].capability_status_updated
 *            - v2.core.account[configuration.customer].capability_status_updated
 *            - v2.core.account[configuration.recipient].capability_status_updated
 *
 *      Local Testing via Stripe CLI:
 *        stripe listen \
 *          --thin-events 'v2.core.account[requirements].updated,v2.core.account[.recipient].capability_status_updated,v2.core.account[configuration.merchant].capability_status_updated,v2.core.account[configuration.customer].capability_status_updated' \
 *          --forward-thin-to http://localhost:8000/webhooks/stripe/thin
 *
 *   2. `POST /webhooks/stripe/billing` — **V1 Snapshot Webhook Endpoint**
 *      Receives full snapshot payloads (NOT thin events) for platform-level
 *      subscriptions, invoices, payment methods, customer billing updates,
 *      tax IDs, and Billing Portal events.
 *
 *      CRITICAL V2 ACCOUNT RULE:
 *        Always identify the connected account using `customer_account`
 *        (shape `acct_...`), NEVER `.customer` (`cus_...`), for V2 accounts!
 */
class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripeConnectService $stripeConnect,
    ) {}

    // =========================================================================
    // 1. V2 THIN EVENTS (Connected Account Requirements & Capabilities)
    // =========================================================================

    /**
     * Handle V2 Thin Event Notifications for Connected Accounts.
     */
    public function handleThinEvents(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature', '');

        if ($sigHeader === '') {
            return response()->json(['error' => 'Missing Stripe-Signature header.'], 400);
        }

        try {
            // Step 1: Verify the webhook signature and parse the thin notification.
            // Uses `$stripeClient->parseEventNotification()` in stripe-php v23.0.0.
            $thinEvent = $this->stripeConnect->parseThinEventNotification($rawPayload, $sigHeader);

            // Step 2: Retrieve the full V2 event from Stripe's V2 Events API
            // so we have complete details about what changed.
            $event = $this->stripeConnect->retrieveV2Event($thinEvent->id, $thinEvent->context);
        } catch (StripeConnectConfigurationException $e) {
            Log::error('Stripe thin webhook misconfigured', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            Log::warning('Stripe thin webhook signature/payload verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid webhook signature or payload.'], 400);
        } catch (ApiErrorException $e) {
            Log::error('Failed to fetch full V2 event from Stripe', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to retrieve V2 event from Stripe.'], 502);
        }

        // Step 3: Route each V2 event type to its dedicated handler.
        $eventType = (string) ($event->type ?? $thinEvent->type ?? '');
        $relatedAccountId = $this->extractV2RelatedAccountId($thinEvent, $event);

        match ($eventType) {
            'v2.core.account[requirements].updated' => $this->handleAccountRequirementsUpdated($event, $relatedAccountId),

            'v2.core.account[configuration.merchant].capability_status_updated' => $this->handleMerchantCapabilityStatusUpdated($event, $relatedAccountId),

            'v2.core.account[configuration.customer].capability_status_updated' => $this->handleCustomerCapabilityStatusUpdated($event, $relatedAccountId),

            // Handle both canonical `configuration.recipient` and CLI shorthand `.recipient`.
            'v2.core.account[configuration.recipient].capability_status_updated',
            'v2.core.account[.recipient].capability_status_updated' => $this->handleRecipientCapabilityStatusUpdated($event, $relatedAccountId),

            default => Log::info('Unhandled Stripe V2 thin event received', [
                'event_id' => $event->id ?? $thinEvent->id,
                'event_type' => $eventType,
                'account_id' => $relatedAccountId,
            ]),
        };

        return response()->json([
            'received' => true,
            'event_id' => $event->id ?? $thinEvent->id,
            'event_type' => $eventType,
        ]);
    }

    /**
     * Handler for `v2.core.account[requirements].updated`.
     *
     * Triggered when financial regulators, card networks, or Stripe require new
     * or updated verification information from a connected account.
     *
     * Note: We intentionally do NOT cache onboarding/requirements status in the
     * database; instead we notify/log so the connected account owner is prompted
     * to generate a fresh Account Link on their dashboard.
     */
    private function handleAccountRequirementsUpdated(V2Event $event, ?string $accountId): void
    {
        Log::warning('Stripe V2 connected account requirements updated — action may be required', [
            'event_id' => $event->id,
            'account_id' => $accountId,
            'created' => $event->created ?? null,
            'next_step' => 'Direct the merchant to /connect so they can complete updated requirements via a new Account Link.',
        ]);

        // In production, you can also send an email/in-app notification to the
        // local user owning `$accountId` prompting them to visit the Connect
        // dashboard and click "Onboard to collect payments" to submit new requirements.
        if ($accountId !== null) {
            $localAccount = StripeConnectedAccount::findByStripeAccountId($accountId);
            if ($localAccount !== null) {
                $this->mergeBillingMetadata($localAccount, [
                    'last_requirements_event_id' => $event->id,
                    'last_requirements_updated_at' => now()->toIso8601String(),
                ]);
            }
        }
    }

    /**
     * Handler for `v2.core.account[configuration.merchant].capability_status_updated`.
     *
     * Triggered whenever a merchant capability (such as `card_payments`) changes
     * status (e.g. `pending` -> `active`, or `active` -> `restricted`).
     */
    private function handleMerchantCapabilityStatusUpdated(V2Event $event, ?string $accountId): void
    {
        $updatedCapability = $event->data->updated_capability ?? null;

        Log::info('Stripe V2 merchant capability status updated', [
            'event_id' => $event->id,
            'account_id' => $accountId,
            'updated_capability' => $updatedCapability,
            'created' => $event->created ?? null,
        ]);

        if ($accountId !== null) {
            $localAccount = StripeConnectedAccount::findByStripeAccountId($accountId);
            if ($localAccount !== null) {
                $this->mergeBillingMetadata($localAccount, [
                    'last_merchant_capability_event_id' => $event->id,
                    'last_merchant_capability_name' => $updatedCapability,
                    'last_merchant_capability_updated_at' => now()->toIso8601String(),
                ]);
            }
        }
    }

    /**
     * Handler for `v2.core.account[configuration.customer].capability_status_updated`.
     *
     * Triggered when a connected account's `customer` configuration capabilities
     * change (for example, automatic indirect tax capability updates).
     */
    private function handleCustomerCapabilityStatusUpdated(V2Event $event, ?string $accountId): void
    {
        $updatedCapability = $event->data->updated_capability ?? null;

        Log::info('Stripe V2 customer capability status updated', [
            'event_id' => $event->id,
            'account_id' => $accountId,
            'updated_capability' => $updatedCapability,
            'created' => $event->created ?? null,
        ]);

        if ($accountId !== null) {
            $localAccount = StripeConnectedAccount::findByStripeAccountId($accountId);
            if ($localAccount !== null) {
                $this->mergeBillingMetadata($localAccount, [
                    'last_customer_capability_event_id' => $event->id,
                    'last_customer_capability_name' => $updatedCapability,
                    'last_customer_capability_updated_at' => now()->toIso8601String(),
                ]);
            }
        }
    }

    /**
     * Handler for `v2.core.account[configuration.recipient].capability_status_updated`.
     *
     * Triggered when recipient payout/transfer capabilities change status.
     */
    private function handleRecipientCapabilityStatusUpdated(V2Event $event, ?string $accountId): void
    {
        $updatedCapability = $event->data->updated_capability ?? null;

        Log::info('Stripe V2 recipient capability status updated', [
            'event_id' => $event->id,
            'account_id' => $accountId,
            'updated_capability' => $updatedCapability,
            'created' => $event->created ?? null,
        ]);

        if ($accountId !== null) {
            $localAccount = StripeConnectedAccount::findByStripeAccountId($accountId);
            if ($localAccount !== null) {
                $this->mergeBillingMetadata($localAccount, [
                    'last_recipient_capability_event_id' => $event->id,
                    'last_recipient_capability_name' => $updatedCapability,
                    'last_recipient_capability_updated_at' => now()->toIso8601String(),
                ]);
            }
        }
    }

    private function extractV2RelatedAccountId(EventNotification $thinEvent, V2Event $event): ?string
    {
        // V2 account events identify the account via `related_object.id` or `context`.
        if (isset($event->related_object->id) && is_string($event->related_object->id)) {
            return $event->related_object->id;
        }
        if (isset($thinEvent->related_object->id) && is_string($thinEvent->related_object->id)) {
            return $thinEvent->related_object->id;
        }
        if (isset($event->context) && is_string($event->context) && $event->context !== '') {
            return $event->context;
        }
        if ($thinEvent->context !== null) {
            $ctx = (string) $thinEvent->context;

            return $ctx !== '' ? $ctx : null;
        }

        return null;
    }

    // =========================================================================
    // 2. V1 SNAPSHOT WEBHOOKS (Platform Subscriptions, Invoices & Billing Portal)
    // =========================================================================

    /**
     * Handle standard V1 **Snapshot** webhooks (NOT thin events) for platform
     * subscriptions billed to V2 Connected Accounts (`customer_account`).
     */
    public function handleBillingEvents(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature', '');

        if ($sigHeader === '') {
            return response()->json(['error' => 'Missing Stripe-Signature header.'], 400);
        }

        try {
            // Verify signature and construct standard V1 snapshot Event.
            $event = $this->stripeConnect->constructSnapshotWebhookEvent($rawPayload, $sigHeader);
        } catch (StripeConnectConfigurationException $e) {
            Log::error('Stripe billing webhook misconfigured', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            Log::warning('Stripe billing webhook signature/payload verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid webhook signature or payload.'], 400);
        }

        $object = $event->data->object ?? null;

        match ($event->type) {
            // Subscription lifecycle: upgrades, downgrades, quantity changes,
            // scheduled cancellations, reactivations, pause/resume collections.
            'customer.subscription.created',
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event, $object),

            // Subscription canceled — revoke platform product/service access.
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event, $object),

            // Invoice paid — confirm payment and ensure subscription stays active.
            'invoice.paid' => $this->handleInvoicePaid($event, $object),

            // Payment method lifecycle.
            'payment_method.attached' => $this->handlePaymentMethodAttached($event, $object),
            'payment_method.detached' => $this->handlePaymentMethodDetached($event, $object),

            // Customer billing profile updated (billing info ONLY — never login email!).
            'customer.updated' => $this->handleCustomerUpdated($event, $object),

            // Customer Tax ID management & validation updates.
            'customer.tax_id.created' => $this->handleCustomerTaxIdUpserted($event, $object, 'created'),
            'customer.tax_id.updated' => $this->handleCustomerTaxIdUpserted($event, $object, 'updated'),
            'customer.tax_id.deleted' => $this->handleCustomerTaxIdDeleted($event, $object),

            // Billing Portal configuration & session lifecycle.
            'billing_portal.configuration.created' => $this->handleBillingPortalConfigurationEvent($event, $object, 'created'),
            'billing_portal.configuration.updated' => $this->handleBillingPortalConfigurationEvent($event, $object, 'updated'),
            'billing_portal.session.created' => $this->handleBillingPortalSessionCreated($event, $object),

            default => Log::info('Unhandled Stripe V1 billing webhook received', [
                'event_id' => $event->id,
                'event_type' => $event->type,
            ]),
        };

        return response()->json([
            'received' => true,
            'event_id' => $event->id,
            'event_type' => $event->type,
        ]);
    }

    /**
     * Handle `customer.subscription.created` and `customer.subscription.updated`.
     *
     * Monitors:
     *   1. Upgrades & downgrades: reads `$subscription->items->data[0]->price->id`
     *      (remember: switching price via Customer Portal ends any active trial immediately).
     *   2. Quantity updates: reads `$subscription->items->data[0]->quantity`.
     *   3. Scheduled cancellation & reactivation: checks `$subscription->cancel_at_period_end`
     *      (`true` = will cancel at period end; `false` = active / reactivated).
     *   4. Paused collections: checks `$subscription->pause_collection` (empty/null = resumed;
     *      otherwise records `behavior` — always `'void'` when paused in portal — and `resumes_at`).
     *   5. Subscription-level `default_payment_method` override.
     */
    private function handleSubscriptionUpdated(V1Event $event, mixed $subscription): void
    {
        // IMPORTANT: For V2 accounts, ALWAYS read `customer_account` (acct_...), NOT `customer`!
        $accountId = $this->extractCustomerAccountId($subscription);
        if ($accountId === null) {
            Log::warning('Subscription webhook missing customer_account', ['event_id' => $event->id]);

            return;
        }

        $record = StripeConnectedAccount::findByStripeAccountId($accountId);
        if ($record === null) {
            Log::warning('Subscription webhook received for unknown connected account', [
                'event_id' => $event->id,
                'customer_account' => $accountId,
            ]);

            return;
        }

        $firstItem = $subscription->items->data[0] ?? null;
        $priceId = is_object($firstItem) ? ($firstItem->price->id ?? $record->subscription_price_id) : $record->subscription_price_id;
        $quantity = is_object($firstItem) && isset($firstItem->quantity)
            ? (int) $firstItem->quantity
            : $record->subscription_quantity;

        // Inspect pause_collection: null/empty means resumed; non-empty means paused.
        $pauseCollection = $subscription->pause_collection ?? null;
        $pauseBehavior = null;
        $pauseResumesAt = null;
        if (is_object($pauseCollection) && ! empty($pauseCollection->behavior)) {
            $pauseBehavior = (string) $pauseCollection->behavior;
            if (! empty($pauseCollection->resumes_at)) {
                $pauseResumesAt = Carbon::createFromTimestamp((int) $pauseCollection->resumes_at);
            }
        }

        $updates = [
            'stripe_subscription_id' => $subscription->id ?? $record->stripe_subscription_id,
            'subscription_status' => $subscription->status ?? $record->subscription_status,
            'subscription_price_id' => $priceId,
            'subscription_quantity' => $quantity,
            'cancel_at_period_end' => (bool) ($subscription->cancel_at_period_end ?? false),
            'pause_collection_behavior' => $pauseBehavior,
            'pause_collection_resumes_at' => $pauseResumesAt,
        ];

        // Track subscription-level default payment method override (or its removal).
        if (isset($subscription->default_payment_method)) {
            $updates['default_payment_method_id'] = is_object($subscription->default_payment_method)
                ? ($subscription->default_payment_method->id ?? null)
                : ($subscription->default_payment_method ?: null);
        }

        $record->update($updates);

        $this->mergeBillingMetadata($record, [
            'last_subscription_event_id' => $event->id,
            'last_subscription_event_type' => $event->type,
            'trial_end' => $subscription->trial_end ?? null,
            'subscription_default_payment_method_override' => $subscription->default_payment_method ?? null,
        ]);

        Log::info('Updated connected account subscription state from webhook', [
            'account_id' => $accountId,
            'subscription_id' => $record->stripe_subscription_id,
            'status' => $record->subscription_status,
            'price_id' => $record->subscription_price_id,
            'quantity' => $record->subscription_quantity,
            'cancel_at_period_end' => $record->cancel_at_period_end,
            'pause_collection_behavior' => $record->pause_collection_behavior,
        ]);
    }

    /**
     * Handle `customer.subscription.deleted`.
     *
     * Marks the subscription as `canceled` so the application immediately
     * revokes access to platform subscription features.
     */
    private function handleSubscriptionDeleted(V1Event $event, mixed $subscription): void
    {
        $accountId = $this->extractCustomerAccountId($subscription);
        if ($accountId === null) {
            return;
        }

        $record = StripeConnectedAccount::findByStripeAccountId($accountId);
        if ($record === null) {
            return;
        }

        $record->update([
            'stripe_subscription_id' => $subscription->id ?? $record->stripe_subscription_id,
            'subscription_status' => 'canceled',
            'cancel_at_period_end' => false,
            'pause_collection_behavior' => null,
            'pause_collection_resumes_at' => null,
        ]);

        $this->mergeBillingMetadata($record, [
            'canceled_at' => now()->toIso8601String(),
            'last_subscription_event_id' => $event->id,
        ]);

        Log::info('Revoked connected account platform subscription after cancellation', [
            'account_id' => $accountId,
            'subscription_id' => $subscription->id ?? null,
        ]);
    }

    /**
     * Handle `invoice.paid`.
     *
     * Confirms subscription invoice payment using `$invoice->customer_account`
     * (never `.customer`) and records the paid invoice metadata in the DB.
     */
    private function handleInvoicePaid(V1Event $event, mixed $invoice): void
    {
        $accountId = $this->extractCustomerAccountId($invoice);
        if ($accountId === null) {
            return;
        }

        $record = StripeConnectedAccount::findByStripeAccountId($accountId);
        if ($record === null) {
            return;
        }

        $updates = [];
        if (! empty($invoice->subscription)) {
            $updates['stripe_subscription_id'] = is_object($invoice->subscription)
                ? ($invoice->subscription->id ?? $record->stripe_subscription_id)
                : (string) $invoice->subscription;
        }
        if ($record->subscription_status === null || in_array($record->subscription_status, ['incomplete', 'past_due', 'unpaid'], true)) {
            $updates['subscription_status'] = 'active';
        }

        if ($updates !== []) {
            $record->update($updates);
        }

        $this->mergeBillingMetadata($record, [
            'last_paid_invoice_id' => $invoice->id ?? null,
            'last_paid_invoice_amount_paid' => $invoice->amount_paid ?? null,
            'last_paid_invoice_currency' => $invoice->currency ?? null,
            'last_paid_invoice_at' => now()->toIso8601String(),
        ]);

        Log::info('Recorded paid subscription invoice for connected account', [
            'account_id' => $accountId,
            'invoice_id' => $invoice->id ?? null,
            'amount_paid' => $invoice->amount_paid ?? null,
        ]);
    }

    /**
     * Handle `payment_method.attached` — customer added a payment method.
     */
    private function handlePaymentMethodAttached(V1Event $event, mixed $paymentMethod): void
    {
        $accountId = $this->extractCustomerAccountId($paymentMethod);
        if ($accountId === null) {
            return;
        }

        $record = StripeConnectedAccount::findByStripeAccountId($accountId);
        if ($record === null) {
            return;
        }

        $pmId = $paymentMethod->id ?? null;
        $meta = $record->billing_metadata ?? [];
        $attached = is_array($meta['attached_payment_methods'] ?? null) ? $meta['attached_payment_methods'] : [];

        if (is_string($pmId) && $pmId !== '') {
            $attached[$pmId] = [
                'id' => $pmId,
                'type' => $paymentMethod->type ?? 'card',
                'brand' => $paymentMethod->card->brand ?? null,
                'last4' => $paymentMethod->card->last4 ?? null,
                'attached_at' => now()->toIso8601String(),
            ];
        }

        $meta['attached_payment_methods'] = $attached;

        $updates = ['billing_metadata' => $meta];
        if ($record->default_payment_method_id === null && is_string($pmId)) {
            $updates['default_payment_method_id'] = $pmId;
        }

        $record->update($updates);

        Log::info('Recorded attached payment method for connected account', [
            'account_id' => $accountId,
            'payment_method_id' => $pmId,
        ]);
    }

    /**
     * Handle `payment_method.detached` — customer removed a payment method.
     */
    private function handlePaymentMethodDetached(V1Event $event, mixed $paymentMethod): void
    {
        $accountId = $this->extractCustomerAccountId($paymentMethod);
        $pmId = $paymentMethod->id ?? null;

        // Note: on `payment_method.detached`, Stripe may clear customer_account on the
        // object itself and place the previous value in `event.data.previous_attributes`.
        if ($accountId === null && isset($event->data->previous_attributes->customer_account)) {
            $accountId = (string) $event->data->previous_attributes->customer_account;
        }

        $record = $accountId !== null
            ? StripeConnectedAccount::findByStripeAccountId($accountId)
            : (is_string($pmId) ? StripeConnectedAccount::query()->where('default_payment_method_id', $pmId)->first() : null);

        if ($record === null) {
            return;
        }

        $meta = $record->billing_metadata ?? [];
        if (is_string($pmId) && isset($meta['attached_payment_methods'][$pmId])) {
            unset($meta['attached_payment_methods'][$pmId]);
        }
        $meta['last_detached_payment_method_id'] = $pmId;

        $updates = ['billing_metadata' => $meta];
        if (is_string($pmId) && $record->default_payment_method_id === $pmId) {
            $updates['default_payment_method_id'] = null;
        }

        $record->update($updates);

        Log::info('Removed detached payment method for connected account', [
            'account_id' => $record->stripe_account_id,
            'payment_method_id' => $pmId,
        ]);
    }

    /**
     * Handle `customer.updated`.
     *
     * Syncs `invoice_settings.default_payment_method` into the database.
     *
     * SECURITY RULE: Treat all updates from `customer.updated` as **billing
     * information changes only** — NEVER use the customer billing email address
     * as a login credential or overwrite authentication credentials.
     */
    private function handleCustomerUpdated(V1Event $event, mixed $customer): void
    {
        $accountId = $this->extractCustomerAccountId($customer);
        if ($accountId === null) {
            return;
        }

        $record = StripeConnectedAccount::findByStripeAccountId($accountId);
        if ($record === null) {
            return;
        }

        $defaultPm = $customer->invoice_settings->default_payment_method ?? null;
        $defaultPmId = is_object($defaultPm) ? ($defaultPm->id ?? null) : ($defaultPm ?: null);

        $record->update([
            'default_payment_method_id' => $defaultPmId,
        ]);

        // Store billing-only email/name updates in billing_metadata, NEVER touching auth credentials.
        $this->mergeBillingMetadata($record, [
            'billing_email' => $customer->email ?? null,
            'billing_name' => $customer->name ?? null,
            'last_customer_updated_event_id' => $event->id,
        ]);

        Log::info('Updated billing info & default payment method from customer.updated', [
            'account_id' => $accountId,
            'default_payment_method_id' => $defaultPmId,
        ]);
    }

    /**
     * Handle `customer.tax_id.created` and `customer.tax_id.updated`.
     *
     * Stores the customer's tax ID and its latest Stripe validation status
     * (`pending`, `verified`, `unverified`, `unavailable`).
     */
    private function handleCustomerTaxIdUpserted(V1Event $event, mixed $taxId, string $action): void
    {
        $accountId = $this->extractCustomerAccountId($taxId);
        if ($accountId === null) {
            return;
        }

        $record = StripeConnectedAccount::findByStripeAccountId($accountId);
        if ($record === null) {
            return;
        }

        $taxIdKey = (string) ($taxId->id ?? '');
        if ($taxIdKey === '') {
            return;
        }

        $taxIds = is_array($record->tax_ids) ? $record->tax_ids : [];
        $taxIds[$taxIdKey] = [
            'id' => $taxIdKey,
            'type' => $taxId->type ?? null,
            'value' => $taxId->value ?? null,
            'country' => $taxId->country ?? null,
            'verification_status' => $taxId->verification->status ?? null,
            'updated_at' => now()->toIso8601String(),
        ];

        $record->update(['tax_ids' => $taxIds]);

        Log::info("Stripe customer tax ID {$action}", [
            'account_id' => $accountId,
            'tax_id' => $taxIdKey,
            'verification_status' => $taxId->verification->status ?? null,
        ]);
    }

    /**
     * Handle `customer.tax_id.deleted` — removes the deleted tax ID from DB.
     */
    private function handleCustomerTaxIdDeleted(V1Event $event, mixed $taxId): void
    {
        $accountId = $this->extractCustomerAccountId($taxId);
        if ($accountId === null) {
            return;
        }

        $record = StripeConnectedAccount::findByStripeAccountId($accountId);
        if ($record === null) {
            return;
        }

        $taxIdKey = (string) ($taxId->id ?? '');
        $taxIds = is_array($record->tax_ids) ? $record->tax_ids : [];
        unset($taxIds[$taxIdKey]);

        $record->update(['tax_ids' => $taxIds]);

        Log::info('Stripe customer tax ID deleted', [
            'account_id' => $accountId,
            'tax_id' => $taxIdKey,
        ]);
    }

    /**
     * Handle `billing_portal.configuration.created` and `billing_portal.configuration.updated`.
     */
    private function handleBillingPortalConfigurationEvent(V1Event $event, mixed $configuration, string $action): void
    {
        Log::info("Stripe Billing Portal configuration {$action}", [
            'event_id' => $event->id,
            'configuration_id' => $configuration->id ?? null,
            'is_default' => $configuration->is_default ?? null,
            'active' => $configuration->active ?? null,
        ]);
    }

    /**
     * Handle `billing_portal.session.created` — logs and records when a
     * connected account opens the Stripe Billing Portal.
     */
    private function handleBillingPortalSessionCreated(V1Event $event, mixed $session): void
    {
        $accountId = $this->extractCustomerAccountId($session);

        Log::info('Stripe Billing Portal session created', [
            'event_id' => $event->id,
            'session_id' => $session->id ?? null,
            'customer_account' => $accountId,
        ]);

        if ($accountId !== null) {
            $record = StripeConnectedAccount::findByStripeAccountId($accountId);
            if ($record !== null) {
                $this->mergeBillingMetadata($record, [
                    'last_billing_portal_session_id' => $session->id ?? null,
                    'last_billing_portal_opened_at' => now()->toIso8601String(),
                ]);
            }
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Extract the V2 Connected Account ID (`acct_...`) from a V1 billing object.
     *
     * CRITICAL: Do NOT fall back to legacy `.customer` (`cus_...`) IDs for V2
     * accounts — always read `customer_account` (or `owner.customer_account` on
     * Tax IDs).
     */
    private function extractCustomerAccountId(mixed $object): ?string
    {
        if (! is_object($object)) {
            return null;
        }

        if (isset($object->customer_account) && is_string($object->customer_account) && $object->customer_account !== '') {
            return $object->customer_account;
        }

        // On TaxId objects, `owner.customer_account` can also carry the V2 account ID.
        if (isset($object->owner) && is_object($object->owner)) {
            $ownerAccount = $object->owner->customer_account ?? null;
            if (is_string($ownerAccount) && $ownerAccount !== '') {
                return $ownerAccount;
            }
        }

        return null;
    }

    /**
     * Merge key-value updates into `billing_metadata` JSON without clobbering existing entries.
     *
     * @param  array<string, mixed>  $changes
     */
    private function mergeBillingMetadata(StripeConnectedAccount $record, array $changes): void
    {
        $existing = is_array($record->billing_metadata) ? $record->billing_metadata : [];
        $record->update([
            'billing_metadata' => array_merge($existing, $changes),
        ]);
    }
}
