<?php

namespace App\Services\Integrations;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FawryService — pay-at-Fawry / card / wallet gateway for Egypt (FawryPay).
 *
 * Flow (server-side only; nothing sensitive touches the browser):
 *   1. charge()        → POST /ECommerceWeb/Fawry/payments/charge      (creates the order)
 *   2. paymentStatus() → GET  /ECommerceWeb/Fawry/payments/status/v2   (server-to-server pull)
 *   3. Fawry's Server Notification V2 POSTs the callback; its
 *      messageSignature covers orderStatus and is verified here via
 *      notificationSignature() from PaymentGatewayService::verifySignature().
 *
 * The status query is the corroboration RUNBOOK §10.4 requires for callbacks
 * whose signature does NOT cover the outcome: the generic
 * sha256(merchantCode + reference + amount + secret) scheme proves only that
 * the caller knows the secret, so handleCallback() asks Fawry directly before
 * granting an entitlement.
 *
 * Config lives in config/integrations.php (FAWRY_* envs). The service refuses
 * to run unless enabled + configured, so environments without credentials keep
 * working — and PaymentGatewayService falls back to its pending-without-
 * entitlement behaviour when it is off.
 */
class FawryService
{
    public function enabled(): bool
    {
        return (bool) config('integrations.fawry.enabled')
            && config('integrations.fawry.merchant_code')
            && config('integrations.fawry.secure_key');
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('integrations.fawry.base_url', 'https://atfawry.fawrystaging.com'), '/');
    }

    /**
     * Create a Fawry order (PAYATFAWRY reference flow): the customer pays the
     * returned merchantRefNum at a Fawry point of sale / in the app; Fawry
     * then notifies our callback URL and/or answers paymentStatus().
     *
     * Signature per Fawry's charge docs:
     *   sha256(merchantCode + merchantRefNum + customerProfileId
     *          + paymentMethod + amount(2dp) + secureKey)
     *
     * @param  array<string, mixed>  $customer  name, email, phone, profile_id
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>  the raw ChargeResponse
     */
    public function charge(float $amount, string $merchantRefNum, array $customer = [], array $items = [], ?string $webhookUrl = null): array
    {
        if (! $this->enabled()) {
            throw new \RuntimeException('Fawry is not enabled/configured (integrations.fawry.*).');
        }

        $merchantCode = (string) config('integrations.fawry.merchant_code');
        $secureKey = (string) config('integrations.fawry.secure_key');
        $method = (string) config('integrations.fawry.payment_method', 'PayAtFawry');
        $profileId = (string) ($customer['profile_id'] ?? config('integrations.fawry.customer_profile_id', '0'));
        $amount2 = self::amount2($amount);

        if ($items === []) {
            $items = [[
                'itemId' => '1',
                'description' => 'Order',
                'price' => $amount2,
                'quantity' => '1',
            ]];
        }

        // Fawry marks mobile/email required for PayAtFawry. Phone may arrive
        // in international form from app_users; pass it through as-is —
        // Fawry validates at their edge and answers with a statusCode.
        $body = [
            'merchantCode' => $merchantCode,
            'merchantRefNum' => $merchantRefNum,
            'customerName' => (string) ($customer['name'] ?? '') ?: 'Customer',
            'customerMobile' => (string) ($customer['phone'] ?? '') ?: '01000000000',
            'customerEmail' => (string) ($customer['email'] ?? '') ?: 'pos@z-syst.local',
            'customerProfileId' => $profileId,
            'amount' => $amount2,
            'currencyCode' => (string) config('integrations.fawry.currency', 'EGP'),
            'language' => (string) config('integrations.fawry.language', 'en-gb'),
            'paymentMethod' => $method,
            'description' => (string) ($items[0]['description'] ?? '') ?: 'Z-Syst payment',
            'chargeItems' => $items,
            // Signed material must include customerProfileId because the body
            // always sends it — "if exist" means "if sent", not "if non-empty".
            'signature' => hash('sha256', $merchantCode.$merchantRefNum.$profileId.$method.$amount2.$secureKey),
        ];
        if ($webhookUrl !== null && $webhookUrl !== '') {
            $body['orderWebHookUrl'] = $webhookUrl;
        }

        $response = Http::baseUrl($this->baseUrl())
            ->timeout((int) config('integrations.http_timeout', 15))
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/ECommerceWeb/Fawry/payments/charge', $body);

        if (! $response->successful()) {
            throw new \RuntimeException('Fawry charge HTTP '.$response->status().'.');
        }

        $json = $response->json();
        // Fawry answers HTTP 200 with an error statusCode (9946 = bad
        // signature, …) — treat non-200 payloads as failure, never as an order.
        if (! is_array($json) || (int) ($json['statusCode'] ?? 0) !== 200) {
            throw new \RuntimeException('Fawry charge failed: '.($json['statusDescription'] ?? 'unknown error').'.');
        }

        return $json;
    }

    /**
     * Server-to-server status pull (GET .../payments/status/v2) — the
     * corroboration hook for handleCallback()'s outcome-not-bound branch.
     * Signature: sha256(merchantCode + merchantRefNumber + secureKey).
     *
     * Returns the raw response, or null when the query is inconclusive
     * (disabled, unreachable, non-2xx) — callers must treat null as "unknown"
     * and leave the transaction pending, never as "not paid".
     *
     * @return array<string, mixed>|null
     */
    public function paymentStatus(string $merchantRefNum): ?array
    {
        if (! $this->enabled() || $merchantRefNum === '') {
            return null;
        }

        $merchantCode = (string) config('integrations.fawry.merchant_code');
        $secureKey = (string) config('integrations.fawry.secure_key');

        try {
            $response = Http::baseUrl($this->baseUrl())
                ->timeout((int) config('integrations.http_timeout', 15))
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/ECommerceWeb/Fawry/payments/status/v2', [
                    'merchantCode' => $merchantCode,
                    'merchantRefNumber' => $merchantRefNum,
                    'signature' => hash('sha256', $merchantCode.$merchantRefNum.$secureKey),
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Fawry status query unreachable; transaction stays pending.', [
                'merchant_ref' => $merchantRefNum,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Fawry status query failed; transaction stays pending.', [
                'merchant_ref' => $merchantRefNum,
                'http_status' => $response->status(),
            ]);

            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }

    /**
     * Order status out of a ChargeResponse / status payload. Fawry's docs are
     * inconsistent about the key (orderStatus in ChargeResponse, paymentStatus
     * in one status example, payment_status in another) — accept all three.
     */
    public static function extractOrderStatus(array $response): ?string
    {
        $raw = $response['orderStatus'] ?? $response['paymentStatus'] ?? $response['payment_status'] ?? null;

        return ($raw === null || $raw === '') ? null : strtoupper((string) $raw);
    }

    /**
     * Two-decimal amount string Fawry signs and expects ("10.00"). null /
     * '' stay empty: a missing field contributes nothing to the
     * concatenation, exactly as Fawry signed it.
     */
    public static function amount2(float|int|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * Server Notification V2 messageSignature:
     *   sha256(fawryRefNumber + merchantRefNumber + paymentAmount(2dp)
     *          + orderAmount(2dp) + orderStatus + paymentMethod
     *          + paymentRefrenceNumber + secureKey)
     *
     * orderStatus is INSIDE the signed material — a valid signature binds the
     * outcome, which is why handleCallback() trusts a verified native
     * notification's status while it refuses to trust the generic scheme's
     * `success` flag. `paymentRefrenceNumber` is Fawry's own field spelling;
     * the corrected spelling is accepted as a fallback.
     *
     * @param  array<string, mixed>  $params
     */
    public static function notificationSignature(array $params, string $secureKey): string
    {
        $paymentAmount = array_key_exists('paymentAmount', $params)
            ? self::amount2($params['paymentAmount'])
            : '';
        $orderAmount = array_key_exists('orderAmount', $params)
            ? self::amount2($params['orderAmount'])
            : '';
        $paymentReference = (string) ($params['paymentRefrenceNumber'] ?? $params['paymentReferenceNumber'] ?? '');

        return hash('sha256',
            ((string) ($params['fawryRefNumber'] ?? ''))
            .((string) ($params['merchantRefNumber'] ?? ''))
            .$paymentAmount
            .$orderAmount
            .((string) ($params['orderStatus'] ?? ''))
            .((string) ($params['paymentMethod'] ?? ''))
            .$paymentReference
            .$secureKey
        );
    }
}
