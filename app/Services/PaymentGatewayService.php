<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\GatewayTransaction;
use App\Services\Integrations\FawryService;
use Illuminate\Support\Facades\Log;

/**
 * Online payment gateways for subscriptions & storefront orders.
 *
 * Drivers default to "simulation" (no credentials needed): a hosted-checkout
 * style flow is faked end-to-end (init → redirect url → webhook callback with
 * HMAC verification). Real Paymob/Fawry credentials switch drivers to live
 * HTTP calls with the same state machine, so no code changes are needed.
 */
class PaymentGatewayService
{
    /**
     * Gateways this service knows how to talk to and how to verify.
     *
     * The gateway name arrives from a URL segment, so it is attacker-controlled
     * on the callback route. It is interpolated into config() keys and into a
     * BusinessSetting key; keeping it inside a closed set means neither can be
     * steered somewhere unintended, and an unknown name fails closed instead of
     * falling through to a signature scheme that was never meant for it.
     */
    public const SUPPORTED_GATEWAYS = ['paymob', 'fawry', 'instapay'];

    public static function isSupported(string $gateway): bool
    {
        return in_array($gateway, self::SUPPORTED_GATEWAYS, true);
    }

    /**
     * Gateways whose callback signature cryptographically covers the OUTCOME.
     *
     * Paymob's HMAC-SHA512 is computed over an ordered field list that includes
     * `success`, so a valid HMAC binds the amount, the transaction id and the
     * outcome together — flipping `success` invalidates it.
     *
     * Fawry and InstaPay sign `sha256(merchantCode + reference + amount +
     * secret)`. Every input to that hash is a value WE hold, not a value the
     * callback carries, so a valid signature proves only "the caller knows the
     * secret and is talking about this transaction for this amount". It says
     * nothing about whether the customer paid. Whoever observes one genuine
     * callback for a transaction — including a declined one — can replay the
     * same URL with `success=true` and the signature still verifies.
     *
     * There is no way to fix that by verifying harder: the outcome is simply not
     * in the signed material, and the scheme cannot be changed unilaterally
     * because the gateway computes it. So for these gateways the callback's
     * `success` flag is not believed. handleCallback() leaves the transaction
     * pending instead of granting the entitlement — unless it can corroborate
     * the outcome some other way: Fawry's native Server Notification V2 carries
     * a `messageSignature` computed over `orderStatus` (verified separately in
     * handleCallback(), because that scheme DOES cover the outcome), and the
     * `outcome_not_bound_to_signature` branch queries Fawry's server-to-server
     * status API before giving up. This constant is about the generic scheme
     * described above, which neither of those changes.
     *
     * @var array<int, string>
     */
    public const OUTCOME_BOUND_GATEWAYS = ['paymob'];

    /**
     * Does a valid signature for this gateway prove the payment succeeded?
     */
    public static function signatureCoversOutcome(string $gateway): bool
    {
        return in_array($gateway, self::OUTCOME_BOUND_GATEWAYS, true);
    }

    /**
     * Paymob's HMAC-SHA512 field list, in the order Paymob specifies.
     *
     * Public so a test can build a valid callback without transcribing the list
     * — a transcription typo there would look exactly like a verification bug.
     * Note that `success` is one of the fields, which is the whole reason
     * paymob is in OUTCOME_BOUND_GATEWAYS and fawry/instapay are not.
     *
     * Dotted keys are read with data_get(), so a real nested Paymob payload
     * (`order.id`, `source_data.pan`, …) resolves properly and a flat one
     * contributes an empty string, exactly as Paymob would have signed it.
     *
     * @var array<int, string>
     */
    public const PAYMOB_HMAC_FIELDS = [
        'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
        'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
        'is_voided', 'order.id', 'owner', 'pending', 'source_data.pan', 'source_data.sub_type',
        'source_data.type', 'success',
    ];

    public function __construct(protected FawryService $fawry)
    {
    }

    /**
     * Open a transaction for a gateway-hosted checkout.
     *
     * For fawry in live mode with credentials configured this also CREATES the
     * order server-side (FawryService::charge()) and stores our merchantRefNum
     * as the transaction reference — that reference is what the notification,
     * the status query and the callback all bind to. On an upstream failure the
     * transaction is recorded failed (reason `fawry_charge_failed`) instead of
     * being left as an orphan pending row.
     *
     * @param  array<string, mixed>  $customer  name/email/phone/profile_id for Fawry's charge body
     */
    public function initiate(int $businessId, string $gateway, string $purpose, ?int $payableId, float $amount, array $customer = []): GatewayTransaction
    {
        abort_unless(self::isSupported($gateway), 404, 'Unknown payment gateway.');

        // Automatic (gateway-hosted) payments are switched off — see
        // config/payments.php. Refusing here rather than only at the HTTP edge
        // means no caller can open a transaction and mint a callback URL while
        // the switch is off: not the admin checkout endpoint, not a console
        // command, not a queue job, not a route added later.
        //
        // The transaction row is the thing that later settles a subscription, so
        // "cannot be created" is a stronger guarantee than "cannot be reached".
        abort_unless(
            (bool) config('payments.automatic_enabled'),
            403,
            'Automatic gateway payments are disabled. Submit a manual payment request instead.'
        );

        $mode = $this->mode($businessId, $gateway);

        $tx = GatewayTransaction::create([
            'business_id' => $businessId,
            'gateway' => $gateway,
            'purpose' => $purpose,
            'payable_id' => $payableId,
            'amount' => $amount,
            'currency' => 'EGP',
            'status' => 'pending',
        ]);

        $payload = [
            'mode' => $mode,
            'redirect_url' => route('web.gateway.callback', [$gateway, $tx->id]),
            'initiated_at' => now()->toIso8601String(),
        ];

        // Live fawry with credentials: create the order at Fawry now so a
        // reference exists for everything downstream to bind to. Simulation and
        // disabled stay HTTP-free — that is what keeps the old behaviour (and
        // the existing tests) intact when no credentials are configured.
        if ($gateway === 'fawry' && $mode === 'live' && $this->fawry->enabled()) {
            $reference = 'ZS'.$tx->id.now()->format('ymdHis');
            $items = [[
                'itemId' => (string) ($payableId ?? $tx->id),
                'description' => (string) $purpose,
                'price' => FawryService::amount2($amount),
                'quantity' => '1',
            ]];

            try {
                $charge = $this->fawry->charge($amount, $reference, $customer, $items, $payload['redirect_url']);
            } catch (\RuntimeException $e) {
                // Record the upstream failure on the transaction so it can
                // never settle later, and let the caller see the reason
                // instead of a redirect that would go nowhere.
                $tx->update([
                    'status' => 'failed',
                    'payload' => array_merge($payload, [
                        'reason' => 'fawry_charge_failed',
                        'error' => $e->getMessage(),
                    ]),
                ]);

                return $tx;
            }

            $payload['fawry'] = $charge;
            $tx->update(['reference' => $reference, 'payload' => $payload]);

            return $tx;
        }

        $tx->update(['payload' => $payload]);

        return $tx;
    }

    public function mode(int $businessId, string $gateway): string
    {
        // Unknown gateway: no mode at all, which makes handleCallback() mark the
        // transaction failed instead of guessing a verification scheme.
        if (! self::isSupported($gateway)) {
            return 'disabled';
        }

        // Simulation is disabled in production — platform keys only
        if (app()->environment('production')) {
            $platformKey = config("services.{$gateway}.api_key") ?? config("integrations.{$gateway}.api_key");
            return $platformKey ? 'live' : 'disabled';
        }
        $apiKey = BusinessSetting::get($businessId, "{$gateway}_api_key");
        return $apiKey ? 'live' : 'simulation';
    }

    /**
     * Verify a gateway webhook/callback and move the transaction on.
     *
     * Paymob: HMAC-SHA512 over an ordered field list that includes `success`.
     * Fawry / InstaPay: SHA256(merchantCode + reference + amount + secret).
     *
     * Verifying the signature is only half the job, and the two schemes are not
     * equivalent. Paymob's covers the outcome; fawry's and instapay's cover the
     * transaction and the amount but NOT whether it was paid — every input to
     * that hash is a value this application already holds. So a valid fawry
     * signature proves authenticity and nothing about success, and the callback's
     * `success` flag is not believed for those gateways.
     *
     * Fawry gets two corroborations instead, both tried before anything is
     * granted:
     *
     *   1. Its native Server Notification V2 POSTs `messageSignature`, computed
     *      over `orderStatus` among other fields — for that scheme the outcome
     *      IS in the signed material, so it is verified and settled directly
     *      (see handleFawryNotification()). A tampered outcome breaks the
     *      signature.
     *   2. Any other live fawry callback falls into the
     *      `outcome_not_bound_to_signature` branch, which queries Fawry's
     *      server-to-server status API (payments/status/v2, signed with the
     *      secure key we hold) and settles from the response — see
     *      corroborateFawry(). Without credentials the query cannot run and the
     *      transaction stays pending, exactly as before.
     *
     * Resulting states:
     *   pending  authenticated callback, outcome not provable (reconcile later)
     *   paid     outcome proven — bound signature, fawry notification/status
     *            query, or simulation
     *   failed   gateway disabled, signature invalid, or reference mismatch
     */
    public function handleCallback(string $gateway, int $txId, array $params): GatewayTransaction
    {
        $tx = GatewayTransaction::where('gateway', $gateway)->findOrFail($txId);
        if ($tx->status === 'paid') {
            return $tx;
        }

        $businessId = (int) $tx->business_id;
        $mode = $this->mode($businessId, $gateway);
        if ($mode === 'disabled') {
            $tx->update(['status' => 'failed', 'payload' => array_merge($tx->payload ?? [], ['callback' => $params, 'verified' => false, 'reason' => 'gateway_disabled'])]);
            return $tx;
        }

        // Fawry's Server Notification V2 carries `messageSignature`, which
        // covers orderStatus — verify it here and settle from the signed
        // outcome; the generic scheme below never sees this payload shape.
        if ($gateway === 'fawry' && $mode !== 'simulation' && isset($params['messageSignature'])) {
            return $this->handleFawryNotification($tx, $params);
        }

        $verified = $mode === 'simulation'
            || $this->verifySignature($gateway, $businessId, $tx, $params);

        if (!$verified) {
            $tx->update(['status' => 'failed', 'payload' => array_merge($tx->payload ?? [], ['callback' => $params, 'verified' => false])]);
            return $tx;
        }

        // The signature is computed over OUR reference, so a callback claiming a
        // different one is describing another transaction. Never overwrite the
        // stored reference from an unauthenticated field either: doing so lets
        // the callback redefine the very value every later verification is keyed
        // on. Fawry's notification names the field `merchantRefNumber`.
        $claimed = isset($params['reference'])
            ? trim((string) $params['reference'])
            : ($gateway === 'fawry' ? trim((string) ($params['merchantRefNumber'] ?? '')) : '');
        if ($claimed !== '' && (string) $tx->reference !== $claimed) {
            $tx->update(['status' => 'failed', 'payload' => array_merge($tx->payload ?? [], [
                'callback' => $params, 'verified' => true, 'reason' => 'reference_mismatch',
            ])]);

            return $tx;
        }

        // Simulation is explicitly non-production — mode() refuses to return it
        // in production — so it may state its own outcome; that is what a
        // simulation is for. Anything live has to be bound or corroborated.
        if ($mode !== 'simulation' && ! self::signatureCoversOutcome($gateway)) {
            // RUNBOOK §10.4: this gateway's signature does not cover the
            // outcome, so corroborate with a server-to-server status query when
            // the gateway offers one (Fawry payments/status/v2). Without
            // credentials the query cannot run and this falls through to the
            // pending state below — the intended loud-at-integration-time
            // behaviour for an uncorroborated integration.
            if ($gateway === 'fawry') {
                $settled = $this->corroborateFawry($tx, $params);
                if ($settled !== null) {
                    return $settled;
                }
            }

            Log::warning('Payment callback verified, but this gateway signs the transaction and not the outcome; entitlement not granted.', [
                'gateway' => $gateway,
                'transaction' => $tx->id,
                'business_id' => $businessId,
                'reason' => 'outcome_not_bound_to_signature',
            ]);

            $tx->update(['status' => 'pending', 'payload' => array_merge($tx->payload ?? [], [
                'callback' => $params,
                'verified' => true,
                // Pending, not failed: the money may genuinely have been taken,
                // and recording a decline invites a second charge. Pending
                // grants no entitlement and stays open for reconciliation —
                // either against the gateway status query, once one exists, or by
                // an operator. See signatureCoversOutcome().
                'reason' => 'outcome_not_bound_to_signature',
            ])]);

            return $tx;
        }

        // Default false, not true. An absent outcome field used to mean "paid",
        // which turns a truncated or stripped callback into a granted
        // entitlement for free.
        $success = filter_var($params['success'] ?? 'false', FILTER_VALIDATE_BOOL);
        $tx->update([
            'status' => $success ? 'paid' : 'failed',
            'reference' => $tx->reference ?: ($claimed !== '' ? $claimed : 'SIM-'.now()->format('YmdHis')),
            'paid_at' => $success ? now() : null,
            'payload' => array_merge($tx->payload ?? [], ['callback' => $params, 'verified' => true]),
        ]);
        $this->grantIfSubscription($tx);

        return $tx;
    }

    protected function verifySignature(string $gateway, int $businessId, GatewayTransaction $tx, array $params): bool
    {
        $secret = $this->secretFor($businessId, $gateway);
        if (!$secret) {
            return false;
        }

        if ($gateway === 'paymob') {
            $concat = collect(self::PAYMOB_HMAC_FIELDS)
                ->map(fn ($k) => (string) data_get($params, $k, ''))
                ->implode('');

            return hash_equals(hash_hmac('sha512', $concat, $secret), (string) ($params['hmac'] ?? ''));
        }

        // fawry / instapay
        $merchantCode = BusinessSetting::get($businessId, $gateway.'_merchant_code', '');
        $concat = $merchantCode.($tx->reference ?? '').(float) $tx->amount.$secret;
        return hash_equals(hash('sha256', $concat), (string) ($params['signature'] ?? ''));
    }

    /**
     * The HMAC/secure key for a gateway: platform config in production (so a
     * tenant setting cannot weaken verification), the business's setting
     * otherwise. Shared by verifySignature() and the native Fawry notification
     * path so both read the same secret.
     */
    protected function secretFor(int $businessId, string $gateway): ?string
    {
        return app()->environment('production')
            ? (config("services.{$gateway}.hmac_secret") ?? config("integrations.{$gateway}.hmac_secret") ?? BusinessSetting::get($businessId, "{$gateway}_hmac_secret"))
            : BusinessSetting::get($businessId, "{$gateway}_hmac_secret");
    }

    /**
     * Settle from Fawry's Server Notification V2.
     *
     * messageSignature = SHA256(fawryRefNumber + merchantRefNumber +
     * paymentAmount + orderAmount + orderStatus + paymentMethod +
     * paymentRefrenceNumber + secureKey) — orderStatus is INSIDE the signed
     * material, so unlike the generic scheme a valid signature proves the
     * outcome. Verification fails closed when no secret is configured.
     *
     * @param  array<string, mixed>  $params
     */
    private function handleFawryNotification(GatewayTransaction $tx, array $params): GatewayTransaction
    {
        $businessId = (int) $tx->business_id;
        $secret = $this->secretFor($businessId, 'fawry');
        $expected = $secret !== null && $secret !== ''
            ? FawryService::notificationSignature($params, $secret)
            : '';
        $given = strtolower((string) ($params['messageSignature'] ?? ''));

        if ($expected === '' || ! hash_equals($expected, $given)) {
            $tx->update(['status' => 'failed', 'payload' => array_merge($tx->payload ?? [], [
                'callback' => $params, 'verified' => false, 'reason' => 'invalid_notification_signature',
            ])]);

            return $tx;
        }

        // The signature proves this is a genuine Fawry notification; merchantRef
        // Number proves it is about THIS row. Both are required — a valid
        // notification for another order of ours must not settle this one.
        $claimed = trim((string) ($params['merchantRefNumber'] ?? ''));
        if ($claimed === '' || (string) $tx->reference !== $claimed) {
            $tx->update(['status' => 'failed', 'payload' => array_merge($tx->payload ?? [], [
                'callback' => $params, 'verified' => true, 'reason' => 'reference_mismatch',
            ])]);

            return $tx;
        }

        // The signed orderAmount must match what we charged. A mismatch means
        // the notification is not describing this transaction's money — do not
        // grant, do not fail (the money may exist somewhere): leave it for
        // reconciliation with a loud reason.
        if (isset($params['orderAmount']) && abs((float) $params['orderAmount'] - (float) $tx->amount) > 0.01) {
            Log::warning('Fawry notification amount does not match the transaction; not settling.', [
                'gateway' => 'fawry',
                'transaction' => $tx->id,
                'order_amount' => $params['orderAmount'],
                'transaction_amount' => $tx->amount,
            ]);
            $tx->update(['status' => 'pending', 'payload' => array_merge($tx->payload ?? [], [
                'callback' => $params, 'verified' => true, 'reason' => 'fawry_amount_mismatch',
            ])]);

            return $tx;
        }

        $orderStatus = strtoupper((string) ($params['orderStatus'] ?? ''));

        if ($orderStatus === 'PAID') {
            $tx->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payload' => array_merge($tx->payload ?? [], [
                    'callback' => $params, 'verified' => true, 'settled_via' => 'fawry_notification',
                ]),
            ]);
            $this->grantIfSubscription($tx);

            return $tx;
        }

        if (in_array($orderStatus, ['FAILED', 'EXPIRED', 'CANCELED'], true)) {
            // Signed by Fawry and covering the outcome: an authoritative
            // negative. Nothing was granted, so recording it cannot invite a
            // double charge the way an unsigned decline would.
            $tx->update(['status' => 'failed', 'payload' => array_merge($tx->payload ?? [], [
                'callback' => $params, 'verified' => true, 'reason' => 'fawry_notification_'.$orderStatus,
            ])]);

            return $tx;
        }

        // NEW / REFUNDED / PARTIAL_REFUNDED / unknown: not settled — pending,
        // not failed, for the same "money may have moved" reason as the generic
        // branch.
        $tx->update(['status' => 'pending', 'payload' => array_merge($tx->payload ?? [], [
            'callback' => $params, 'verified' => true, 'reason' => 'fawry_notification_'.$orderStatus,
        ])]);

        return $tx;
    }

    /**
     * Corroborate an outcome-not-bound fawry callback with Fawry's own
     * server-to-server status query (payments/status/v2). The request is
     * signed with the secure key we hold, so the response is authoritative —
     * unlike the callback's unsigned `success` flag.
     *
     * Returns the settled transaction, or null when the query was unavailable
     * (no credentials, no reference, HTTP failure) or inconclusive — the caller
     * then keeps the transaction pending.
     *
     * @param  array<string, mixed>  $params
     */
    private function corroborateFawry(GatewayTransaction $tx, array $params): ?GatewayTransaction
    {
        if (! $this->fawry->enabled() || $tx->reference === null || $tx->reference === '') {
            return null;
        }

        $status = $this->fawry->paymentStatus((string) $tx->reference);
        if ($status === null) {
            return null;
        }

        // An error payload (order not found, bad signature) carries no
        // trustworthy orderStatus.
        if (isset($status['statusCode']) && (int) $status['statusCode'] !== 200) {
            return null;
        }

        $orderStatus = FawryService::extractOrderStatus($status);
        $orderAmount = isset($status['orderAmount']) ? (float) $status['orderAmount'] : null;

        if ($orderStatus === 'PAID') {
            if ($orderAmount !== null && abs($orderAmount - (float) $tx->amount) > 0.01) {
                Log::warning('Fawry status query reports PAID for a different amount; not settling.', [
                    'gateway' => 'fawry',
                    'transaction' => $tx->id,
                    'order_amount' => $orderAmount,
                    'transaction_amount' => $tx->amount,
                ]);

                return null;
            }

            $tx->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payload' => array_merge($tx->payload ?? [], [
                    'callback' => $params, 'verified' => true, 'settled_via' => 'fawry_status_query',
                ]),
            ]);
            $this->grantIfSubscription($tx);

            return $tx;
        }

        if (in_array($orderStatus, ['FAILED', 'EXPIRED', 'CANCELED'], true)) {
            // The query is authenticated by the signature WE put on the
            // request, so this negative is authoritative: the order is dead
            // and never completed.
            $tx->update(['status' => 'failed', 'payload' => array_merge($tx->payload ?? [], [
                'callback' => $params, 'verified' => true, 'reason' => 'fawry_status_'.$orderStatus,
            ])]);

            return $tx;
        }

        // NEW / WAITING / unknown: inconclusive, caller leaves it pending.
        return null;
    }

    /**
     * A paid subscription transaction activates the subscription.
     */
    private function grantIfSubscription(GatewayTransaction $tx): void
    {
        if ($tx->status === 'paid' && $tx->purpose === 'subscription') {
            \App\Models\Subscription::where('id', $tx->payable_id)->first()?->update(['status' => 'active']);
        }
    }
}
