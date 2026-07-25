<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Enums\TransactionStatus;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Paymob Gateway - Main Egyptian Payment Aggregator
 *
 * يدعم: Vodafone Cash, Orange Cash, Etisalat Cash, We Pay, InstaPay, وبطاقات الدفع
 * وثائق API: https://docs.paymob.com
 */
class PaymobGateway extends BaseGateway
{
    private const API_BASE = 'https://accept.paymob.com/api';

    protected function getConfigPrefix(): string
    {
        return 'paymob';
    }

    protected function loadConfig(): void
    {
        $this->config = [
            'api_key' => config('payments.gateways.paymob.api_key') ?? config('services.paymob.api_key'),
            'merchant_id' => config('payments.gateways.paymob.merchant_id') ?? config('services.paymob.merchant_id'),
            'hmac_secret' => config('payments.gateways.paymob.hmac_secret') ?? config('services.paymob.hmac_secret'),
            'iframe_id' => config('payments.gateways.paymob.iframe_id') ?? config('services.paymob.iframe_id'),
            'environment' => config('payments.gateways.paymob.environment', 'sandbox'),
            'webhook_url' => config('payments.gateways.paymob.webhook_url') ?? config('services.paymob.webhook_url'),

            'integration_ids' => config('payments.gateways.paymob.integration_ids') ?? [
                'vodafone_cash' => env('PAYMOB_INTEGRATION_VODAFONE'),
                'orange_cash' => env('PAYMOB_INTEGRATION_ORANGE'),
                'etisalat_cash' => env('PAYMOB_INTEGRATION_ETISALAT'),
                'we_pay' => env('PAYMOB_INTEGRATION_WE'),
                'instapay' => env('PAYMOB_INTEGRATION_INSTAPAY'),
                'card' => env('PAYMOB_INTEGRATION_CARD'),
            ],

            'min_amount' => config('payments.limits.min_amount', 1),
            'max_amount' => config('payments.limits.max_amount', 50000),
            'timeout' => config('payments.gateways.paymob.timeout', 30),
            'test_mode' => $this->config['environment'] === 'sandbox',
        ];

        $this->isTestMode = $this->config['test_mode'] ?? false;
    }

    public function getMethodType(): PaymentMethodType
    {
        return PaymentMethodType::VODAFONE_CASH; // البوابة تدعم جميع الطرق
    }

    public function getDisplayName(): string
    {
        return 'Paymob Accept';
    }

    public function getRequiredConfig(): array
    {
        return [
            'api_key',
            'merchant_id',
        ];
    }

    /**
     * Initiate payment through Paymob
     */
    public function initiate(array $data): PaymentTransaction
    {
        $methodType = PaymentMethodType::from($data['payment_method_type'] ?? 'vodafone_cash');
        $amount = (float) $data['amount'];

        $this->validateAmount($amount);

        // 1. Authenticate with Paymob
        $authToken = $this->authenticate();

        // 2. Create Order
        $order = $this->createOrder($authToken, $data);

        // 3. Create Payment Key
        $paymentKey = $this->getPaymentKey($authToken, $order['id'], $data);

        // 4. Create local transaction record
        $transaction = $this->createTransaction([
            'amount' => $amount,
            'currency' => $data['currency'] ?? 'EGP',
            'mobile_number' => $data['mobile_number'] ?? null,
            'wallet_provider' => $this->getWalletProvider($methodType),
            'reference_id' => $data['reference_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'description' => $data['description'] ?? 'Payment via Paymob',
            'metadata' => $data['metadata'] ?? [],
            'user_id' => $data['user_id'] ?? auth()->id(),
        ]);

        // 5. Update with payment key for merchant reference
        $transaction->update([
            'external_reference' => "PMB-{$order['id']}",
            'status' => TransactionStatus::PROCESSING->value,
            'processed_at' => now(),
        ]);

        $this->log('info', 'Payment initiated via Paymob', [
            'transaction_id' => $transaction->id,
            'order_id' => $order['id'],
            'payment_method' => $methodType->value,
        ]);

        return $transaction->fresh();
    }

    /**
     * Verify payment status
     */
    public function verify(PaymentTransaction $transaction): PaymentTransaction
    {
        if (!$transaction->external_reference) {
            throw PaymentException::invalidTransaction('No external reference to verify');
        }

        $authToken = $this->authenticate();

        $orderId = str_replace('PMB-', '', $transaction->external_reference);

        $response = $this->httpRequest('GET', self::API_BASE . "/orders/{$orderId}", [
            'headers' => [
                'Authorization' => "Bearer {$authToken}",
                'Content-Type' => 'application/json',
            ],
        ]);

        if ($response['success']) {
            $orderData = $response['data'];

            foreach ($orderData['transactions'] ?? [] as $tx) {
                $txStatus = $tx['success'] ?? false;

                if ($txStatus) {
                    $transaction->markAsCompleted($tx['id']);
                } else {
                    $transaction->markAsFailed($tx['data']['message'] ?? 'Payment failed');
                }
            }

            $this->log('info', 'Payment verification result', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
            ]);
        }

        return $transaction->fresh();
    }

    /**
     * Handle webhook from Paymob
     */
    public function handleWebhook(array $payload): ?PaymentTransaction
    {
        $obj = $payload['obj'] ?? [];
        $hmac = $payload['hmac'] ?? '';

        // Verify HMAC signature
        if (!$this->verifyHmac($hmac, $payload)) {
            $this->log('warning', 'Invalid Paymob webhook HMAC', [
                'hmac' => $hmac,
            ]);
            return null;
        }

        $transactionId = $obj['id'] ?? null;
        $orderId = $obj['order']['id'] ?? null;

        if (!$orderId) {
            $this->log('warning', 'Webhook without order ID');
            return null;
        }

        $transaction = PaymentTransaction::where('external_reference', "PMB-{$orderId}")
            ->orWhere('external_transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            $this->log('warning', 'Webhook for unknown transaction', [
                'order_id' => $orderId,
            ]);
            return null;
        }

        // Update status based on webhook
        $isSuccess = $obj['success'] ?? false;

        if ($isSuccess) {
            $transaction->markAsCompleted($transactionId);
        } else {
            $transaction->markAsFailed($obj['data']['message'] ?? 'Payment failed');
        }

        // Mark webhook as received
        $transaction->update([
            'webhook_received' => true,
            'webhook_payload' => $payload,
        ]);

        $this->log('info', 'Webhook processed successfully', [
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
        ]);

        return $transaction->fresh();
    }

    /**
     * Verify HMAC signature from Paymob webhook
     * Uses constant-time comparison to prevent timing attacks
     *
     * Important: The HMAC is computed over the request body WITHOUT the hmac field itself
     */
    protected function verifyHmac(string $hmac, array $payload): bool
    {
        // Validate inputs
        if (empty($hmac)) {
            $this->log('warning', 'HMAC verification failed: empty hmac provided');
            return false;
        }

        if (empty($this->config['hmac_secret'])) {
            $this->log('error', 'HMAC verification failed: hmac_secret not configured');
            return false;
        }

        // Remove HMAC field before computing signature
        $payloadForVerification = $payload;
        unset($payloadForVerification['hmac']);

        // Compute HMAC using SHA512
        $computedHmac = hash_hmac(
            'sha512',
            json_encode($payloadForVerification, JSON_UNESCAPED_UNICODE),
            $this->config['hmac_secret'],
            false // return as hex string
        );

        // Use constant-time comparison to prevent timing attacks
        $isValid = hash_equals($computedHmac, $hmac);

        if (!$isValid) {
            $this->log('warning', 'HMAC verification failed: signature mismatch', [
                'expected' => substr($hmac, 0, 16) . '...',
                'received' => substr($computedHmac, 0, 16) . '...',
            ]);
        }

        return $isValid;
    }

    /**
     * Get authentication token from Paymob
     * BUG-S6 FIX: Cache token with TTL to avoid excessive API calls and handle expiration
     */
    protected function authenticate(): string
    {
        $cacheKey = 'paymob_auth_token_' . md5($this->config['api_key']);
        $cacheTTL = 3590; // Token valid for 1 hour, cache for 59m 50s to be safe

        // Try to get token from cache
        $cachedToken = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if (!empty($cachedToken)) {
            $this->log('debug', 'Using cached Paymob auth token');
            return $cachedToken;
        }

        // Get new token from API
        $response = $this->httpRequest('POST', self::API_BASE . '/auth/tokens', [
            'json' => [
                'api_key' => $this->config['api_key'],
            ],
        ]);

        if (!$response['success'] || empty($response['data']['token'])) {
            throw PaymentException::authenticationFailed($this->getDisplayName());
        }

        $token = $response['data']['token'];

        // Cache the token
        \Illuminate\Support\Facades\Cache::put($cacheKey, $token, $cacheTTL);

        $this->log('info', 'Successfully obtained and cached Paymob auth token');

        return $token;
    }

    /**
     * Create order in Paymob
     */
    protected function createOrder(string $authToken, array $data): array
    {
        $response = $this->httpRequest('POST', self::API_BASE . '/ecommerce/orders', [
            'headers' => [
                'Authorization' => "Bearer {$authToken}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'auth_token' => $authToken,
                'delivery_needed' => 'false',
                'amount_cents' => $this->formatAmount($data['amount']),
                'currency' => $data['currency'] ?? 'EGP',
                'merchant_order_id' => uniqid('order_'),
                'items' => [],
            ],
        ]);

        if (!$response['success']) {
            throw PaymentException::initiationFailed($this->getDisplayName(), $response['data']);
        }

        return $response['data'];
    }

    /**
     * Get payment key for the transaction
     */
    protected function getPaymentKey(string $authToken, int $orderId, array $data): array
    {
        $methodType = PaymentMethodType::from($data['payment_method_type'] ?? 'vodafone_cash');
        $integrationId = $this->config['integration_ids'][$methodType->value] ?? null;

        $response = $this->httpRequest('POST', self::API_BASE . '/accept/payment_keys', [
            'headers' => [
                'Authorization' => "Bearer {$authToken}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'auth_token' => $authToken,
                'amount_cents' => $this->formatAmount($data['amount']),
                'expiration' => 3600,
                'order_id' => $orderId,
                'integration_id' => $integrationId,
                'billing_data' => $this->getBillingData($data),
                'lock_order_when_paid' => true,
            ],
        ]);

        return $response['data'] ?? [];
    }

    /**
     * Get wallet provider name
     */
    protected function getWalletProvider(PaymentMethodType $type): ?string
    {
        return match($type) {
            PaymentMethodType::VODAFONE_CASH => 'vodafone',
            PaymentMethodType::ORANGE_CASH => 'orange',
            PaymentMethodType::ETISALAT_CASH => 'etisalat',
            PaymentMethodType::INSTAPAY => 'instapay',
            PaymentMethodType::WE_PAY => 'we',
            default => null,
        };
    }

    /**
     * Get billing data for Paymob
     */
    protected function getBillingData(array $data): array
    {
        return [
            'first_name' => $data['customer_name'] ?? 'Customer',
            'last_name' => ' ',
            'email' => $data['customer_email'] ?? 'customer@example.com',
            'phone_number' => $data['mobile_number'] ?? null,
            'street' => ' ',
            'building' => ' ',
            'floor' => ' ',
            'apartment' => ' ',
            'city' => 'Cairo',
            'country' => 'EG',
            'postal_code' => ' ',
            'landmark' => ' ',
        ];
    }

    /**
     * Format amount to cents (Paymob uses integer cents)
     */
    public function formatAmount(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Generate QR code for InstaPay
     */
    public function generateQrCode(PaymentTransaction $transaction): ?string
    {
        if ($transaction->payment_method_type !== PaymentMethodType::INSTAPAY->value) {
            return null;
        }

        // InstaPay uses reference number + QR code
        // The QR code contains the reference number that customer scans
        if ($transaction->external_reference) {
            // In production, this would generate actual QR code
            // For now, return the reference that can be encoded to QR
            return $transaction->external_reference;
        }

        return null;
    }

    /**
     * Get external transaction status
     */
    public function getExternalTransactionStatus(string $externalTransactionId): array
    {
        try {
            $authToken = $this->authenticate();

            $response = $this->httpRequest('GET',
                self::API_BASE . "/transactions/{$externalTransactionId}",
                [
                    'headers' => [
                        'Authorization' => "Bearer {$authToken}",
                    ],
                ]
            );

            return [
                'success' => $response['success'],
                'status' => $response['data']['success'] ?? false ? 'completed' : 'pending',
                'data' => $response['data'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process payment (not separate in Paymob - handled by initiate)
     */
    public function process(PaymentTransaction $transaction, array $data = []): PaymentTransaction
    {
        // Paymob handles mobile wallets via redirect/USSD
        // Just verify the status
        return $this->verify($transaction);
    }

    /**
     * Process refund
     */
    public function refund(PaymentTransaction $transaction, ?float $amount = null, ?string $reason = null): PaymentTransaction
    {
        if (!$transaction->isCompleted()) {
            throw PaymentException::cannotRefund($this->getDisplayName(), 'Payment not completed');
        }

        $refundAmount = $amount ?? $transaction->amount;

        if ($refundAmount > $transaction->amount) {
            throw PaymentException::invalidAmount('Refund amount exceeds original amount');
        }

        try {
            $authToken = $this->authenticate();

            $response = $this->httpRequest('POST', self::API_BASE . '/ecommerce/orders/refund', [
                'headers' => [
                    'Authorization' => "Bearer {$authToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'auth_token' => $authToken,
                    'order_id' => str_replace('PMB-', '', $transaction->external_reference),
                    'amount_cents' => $this->formatAmount($refundAmount),
                ],
            ]);

            if ($response['success']) {
                $isFullRefund = $refundAmount >= $transaction->amount;
                $transaction->markAsRefunded($refundAmount, $reason);

                if (!$isFullRefund) {
                    $transaction->update([
                        'status' => TransactionStatus::PARTIALLY_REFUNDED->value,
                    ]);
                }

                $this->log('info', 'Refund processed successfully', [
                    'transaction_id' => $transaction->id,
                    'amount' => $refundAmount,
                ]);
            } else {
                throw PaymentException::refundFailed(
                    $this->getDisplayName(),
                    $response['message'] ?? 'Unknown error'
                );
            }
        } catch (\Exception $e) {
            if ($e instanceof PaymentException) {
                throw $e;
            }
            throw PaymentException::refundFailed($this->getDisplayName(), $e->getMessage());
        }

        return $transaction->fresh();
    }
}
