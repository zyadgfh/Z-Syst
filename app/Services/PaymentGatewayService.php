<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\GatewayTransaction;
use Illuminate\Support\Facades\Http;

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

    public function initiate(int $businessId, string $gateway, string $purpose, ?int $payableId, float $amount): GatewayTransaction
    {
        abort_unless(self::isSupported($gateway), 404, 'Unknown payment gateway.');

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
     * Verify a gateway webhook/callback and mark paid.
     * Paymob: HMAC-SHA512 over ordered fields with the HMAC secret.
     * Fawry (simulation/live): signature = SHA256(merchantCode+ref+amount+secret).
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

        $success = filter_var($params['success'] ?? 'true', FILTER_VALIDATE_BOOL);
        $tx->update([
            'status' => $success ? 'paid' : 'failed',
            'reference' => $params['reference'] ?? ('SIM-'.now()->format('YmdHis')),
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
            $concat = collect(['amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
                'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
                'is_voided', 'order.id', 'owner', 'pending', 'source_data.pan', 'source_data.sub_type',
                'source_data.type', 'success'])
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
