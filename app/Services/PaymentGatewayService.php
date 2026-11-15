<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\GatewayTransaction;
use Illuminate\Support\Facades\Http;
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
     * pending instead of granting the entitlement; see the note there for what a
     * real integration has to add.
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

    public function initiate(int $businessId, string $gateway, string $purpose, ?int $payableId, float $amount): GatewayTransaction
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

        $tx = GatewayTransaction::create([
            'business_id' => $businessId,
            'gateway' => $gateway,
            'purpose' => $purpose,
            'payable_id' => $payableId,
            'amount' => $amount,
            'currency' => 'EGP',
            'status' => 'pending',
        ]);

        $tx->update(['payload' => [
            'mode' => $this->mode($businessId, $gateway),
            'redirect_url' => route('web.gateway.callback', [$gateway, $tx->id]),
            'initiated_at' => now()->toIso8601String(),
        ]]);

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
     * `success` flag is not believed for those gateways: the transaction is left
     * pending with reason `outcome_not_bound_to_signature` rather than being
     * marked paid. See signatureCoversOutcome().
     *
     * Resulting states:
     *   pending  authenticated callback, outcome not provable (reconcile later)
     *   paid     outcome proven — bound signature, or simulation
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
        // on.
        $claimed = isset($params['reference']) ? trim((string) $params['reference']) : '';
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
                // either against a gateway status query, once one exists, or by
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

        if ($tx->status === 'paid' && $tx->purpose === 'subscription') {
            // Mark the subscription as paid via the existing plan manager.
            \App\Models\Subscription::where('id', $tx->payable_id)->first()?->update(['status' => 'active']);
        }

        return $tx;
    }

    protected function verifySignature(string $gateway, int $businessId, GatewayTransaction $tx, array $params): bool
    {
        // In production, HMAC secret comes from platform config, not tenant settings
        $secret = app()->environment('production')
            ? (config("services.{$gateway}.hmac_secret") ?? config("integrations.{$gateway}.hmac_secret") ?? BusinessSetting::get($businessId, "{$gateway}_hmac_secret"))
            : BusinessSetting::get($businessId, "{$gateway}_hmac_secret");
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
}
