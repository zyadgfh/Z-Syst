<?php

namespace App\Services\Integrations;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PaymobService — card + mobile-wallet gateway for Egypt (Accept/Paymob).
 *
 * Flow (server-side only; nothing sensitive touches the browser):
 *   1. auth()            → POST /api/auth/tokens            (api key → token)
 *   2. registerOrder()   → POST /api/ecommerce/orders       (amount_cents → order id)
 *   3. paymentKey()      → POST /api/acceptance/payment_keys (token+order → payment key)
 *   4. iframeUrl()       → https://accept.paymob.com/api/acceptance/iframes/{IFRAME_ID}?payment_token={key}
 *
 * Config lives in config/integrations.php (PAYMOB_* envs). The service
 * refuses to run unless enabled + configured, so staging environments
 * without credentials keep working.
 */
class PaymobService
{
    public function enabled(): bool
    {
        return (bool) config('integrations.paymob.enabled')
            && config('integrations.paymob.api_key')
            && config('integrations.paymob.iframe_id');
    }

    public function iframeUrl(float $amount, string $merchantOrderId, array $customer, array $items = []): string
    {
        if (! $this->enabled()) {
            throw new \RuntimeException('Paymob is not enabled/configured (integrations.paymob.*).');
        }

        $base = rtrim(config('integrations.paymob.base_url', 'https://accept.paymob.com/api'), '/');
        $amountCents = (int) round($amount * 100);

        $auth = Http::baseUrl($base)
            ->timeout(config('integrations.http_timeout', 15))
            ->post('/auth/tokens', ['api_key' => config('integrations.paymob.api_key')])
            ->throw()
            ->json('token');

        $orderId = Http::baseUrl($base)
            ->timeout(config('integrations.http_timeout', 15))
            ->post('/ecommerce/orders', [
                'auth_token' => $auth,
                'delivery_needed' => false,
                'amount_cents' => $amountCents,
                'currency' => config('integrations.paymob.currency', 'EGP'),
                'merchant_order_id' => $merchantOrderId, // unique per business sale
                'items' => $items,
            ])
            ->throw()
            ->json('id');

        $paymentKey = Http::baseUrl($base)
            ->timeout(config('integrations.http_timeout', 15))
            ->post('/acceptance/payment_keys', [
                'auth_token' => $auth,
                'amount_cents' => $amountCents,
                'expiration' => 3600,
                'order_id' => $orderId,
                'currency' => config('integrations.paymob.currency', 'EGP'),
                'integration_id' => (int) config('integrations.paymob.integration_id'),
                'billing_data' => [
                    'first_name' => $customer['first_name'] ?? 'Customer',
                    'last_name' => $customer['last_name'] ?? 'ZSyst',
                    'email' => $customer['email'] ?? 'pos@z-syst.local',
                    'phone_number' => $customer['phone'] ?? '+20000000000',
                    // Paymob requires these even for online payment:
                    'apartment' => 'NA', 'floor' => 'NA', 'street' => 'NA',
                    'building' => 'NA', 'shipping_method' => 'NA', 'postal_code' => 'NA',
                    'city' => $customer['city'] ?? 'Cairo', 'country' => 'EG', 'state' => $customer['state'] ?? 'Cairo',
                ],
            ])
            ->throw()
            ->json('token');

        return sprintf(
            'https://accept.paymob.com/api/acceptance/iframes/%s?payment_token=%s',
            config('integrations.paymob.iframe_id'),
            $paymentKey
        );
    }

    /**
     * Verify a TRANSACTION webhook HMAC (docs: HMAC calculation over a
     * concatenated field list, sorted by key, with the HMAC secret).
     */
    public function verifyWebhookHmac(array $payload, string $receivedHmac): bool
    {
        $secret = config('integrations.paymob.hmac_secret');
        if (! $secret) {
            return false;
        }

        $obj = $payload['obj'] ?? [];
        $keys = config('integrations.paymob.hmac_keys', [
            'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
            'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded',
            'is_standalone_payment', 'is_voided', 'order.id', 'owner', 'pending',
            'source_data.pan', 'source_data.sub_type', 'source_data.type', 'success',
        ]);

        $concat = '';
        foreach ($keys as $dotKey) {
            $value = $obj;
            foreach (explode('.', $dotKey) as $part) {
                $value = $value[$part] ?? '';
            }
            $concat .= (is_bool($value) ? var_export($value, true) : (string) $value);
        }

        $calculated = hash_hmac('sha512', $concat, $secret);

        if (! hash_equals($calculated, strtolower($receivedHmac))) {
            Log::warning('Paymob webhook HMAC mismatch', ['order' => $obj['order']['id'] ?? null]);
            return false;
        }

        return true;
    }
}
