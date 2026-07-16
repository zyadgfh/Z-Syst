<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Enums\TransactionStatus;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;

/**
 * Vodafone Cash Payment Gateway Integration
 * 
 * تكامل مع محفظة فودافون كاش للدفع الإلكتروني
 * يدعم: USSD Push, QR Code, Webhook, Refunds
 */
class VodafoneCashGateway extends BaseGateway
{
    protected function getConfigPrefix(): string
    {
        return 'vodafone_cash';
    }

    protected function loadConfig(): void
    {
        $this->config = [
            'api_key' => config('services.vodafone_cash.api_key'),
            'api_secret' => config('services.vodafone_cash.api_secret'),
            'merchant_id' => config('services.vodafone_cash.merchant_id'),
            'base_url' => config('services.vodafone_cash.base_url', 'https://api.vodafone.com.eg/cash'),
            'webhook_url' => config('services.vodafone_cash.webhook_url'),
            'callback_url' => config('services.vodafone_cash.callback_url'),
            'min_amount' => config('services.vodafone_cash.min_amount', 1),
            'max_amount' => config('services.vodafone_cash.max_amount', 50000),
            'timeout' => config('services.vodafone_cash.timeout', 30),
            'test_mode' => config('services.vodafone_cash.test_mode', env('APP_DEBUG', true)),
        ];

        $this->isTestMode = $this->config['test_mode'] ?? true;
    }

    public function getMethodType(): PaymentMethodType
    {
        return PaymentMethodType::VODAFONE_CASH;
    }

    public function getDisplayName(): string
    {
        return 'Vodafone Cash';
    }

    public function getRequiredConfig(): array
    {
        return [
            'api_key',
            'merchant_id',
            'base_url',
        ];
    }

    /**
     * Initiate a Vodafone Cash payment.
     * يدعم: الدفع برقم المحفظة أو QR Code
     */
    public function initiate(array $data): PaymentTransaction
    {
        $amount = $data['amount'];
        $this->validateAmount($amount);

        $mobileNumber = $data['mobile_number'] ?? null;
        if (empty($mobileNumber)) {
            throw new PaymentException(
                'Mobile number is required for Vodafone Cash payment.',
                422,
                gatewayName: $this->getDisplayName()
            );
        }

        // Validate Egyptian mobile number (01XX XXX XXXX)
        if (!preg_match('/^01[0-9]{9}$/', $mobileNumber)) {
            throw new PaymentException(
                'Invalid Egyptian mobile number format. Must be 11 digits starting with 01.',
                422,
                gatewayName: $this->getDisplayName()
            );
        }

        $transaction = $this->createTransaction([
            'amount' => $amount,
            'currency' => $data['currency'] ?? 'EGP',
            'mobile_number' => $mobileNumber,
            'wallet_provider' => 'vodafone',
            'reference_id' => $data['reference_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'description' => $data['description'] ?? 'Vodafone Cash Payment',
            'metadata' => $data['metadata'] ?? [],
            'user_id' => $data['user_id'] ?? auth()->id(),
        ]);

        try {
            // Initiate payment with Vodafone Cash API
            $response = $this->httpRequest('POST', $this->config['base_url'] . '/payment/initiate', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                    'X-Merchant-ID' => $this->config['merchant_id'],
                ],
                'json' => [
                    'merchant_id' => $this->config['merchant_id'],
                    'transaction_id' => $transaction->external_reference,
                    'amount' => $this->formatAmount($amount),
                    'currency' => 'EGP',
                    'mobile_number' => $mobileNumber,
                    'callback_url' => $this->config['callback_url'],
                    'description' => $transaction->description,
                    'payment_type' => 'ussd_push', // USSD Push for instant payment
                ],
            ]);

            if ($response['success']) {
                $data = $response['data'];
                
                $transaction->update([
                    'external_transaction_id' => $data['transaction_id'] ?? null,
                    'payment_url' => $data['payment_url'] ?? null,
                    'qr_code_url' => $data['qr_code_url'] ?? null,
                    'qr_code_data' => $data['qr_code_data'] ?? null,
                    'status' => TransactionStatus::PROCESSING->value,
                    'processed_at' => now(),
                ]);

                $this->log('info', 'Payment initiated successfully', [
                    'transaction_id' => $transaction->id,
                    'external_id' => $data['transaction_id'] ?? null,
                    'mobile' => $mobileNumber,
                ]);
            } else {
                $transaction->markAsFailed($response['data']['message'] ?? 'Initiation failed');
                
                throw new PaymentException(
                    $response['data']['message'] ?? 'Failed to initiate Vodafone Cash payment.',
                    400,
                    gatewayName: $this->getDisplayName()
                );
            }
        } catch (\Exception $e) {
            $transaction->markAsFailed($e->getMessage());
            throw $e;
        }

        return $transaction->fresh();
    }

    /**
     * Process/confirm a Vodafone Cash payment.
     */
    public function process(PaymentTransaction $transaction, array $data = []): PaymentTransaction
    {
        if (!$transaction->isPending() && !$transaction->isCompleted()) {
            throw new PaymentException(
                'Transaction cannot be processed in its current state.',
                400,
                gatewayName: $this->getDisplayName()
            );
        }

        // If transaction has external ID, verify with API
        if ($transaction->external_transaction_id) {
            return $this->verify($transaction);
        }

        // Otherwise, try to process the payment
        try {
            $response = $this->httpRequest('POST', $this->config['base_url'] . '/payment/process', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'transaction_id' => $transaction->external_reference,
                    'merchant_id' => $this->config['merchant_id'],
                    'otp' => $data['otp'] ?? null, // OTP for verification if needed
                ],
            ]);

            if ($response['success']) {
                $transaction->markAsCompleted($response['data']['transaction_id'] ?? null);
                
                $this->log('info', 'Payment processed successfully', [
                    'transaction_id' => $transaction->id,
                    'external_id' => $transaction->external_transaction_id,
                ]);
            } else {
                $transaction->markAsFailed($response['data']['message'] ?? 'Processing failed');
            }
        } catch (\Exception $e) {
            $transaction->markAsFailed($e->getMessage());
            throw $e;
        }

        return $transaction->fresh();
    }

    /**
     * Verify a Vodafone Cash payment transaction.
     */
    public function verify(PaymentTransaction $transaction): PaymentTransaction
    {
        if (!$transaction->external_transaction_id) {
            throw new PaymentException(
                'No external transaction ID to verify.',
                400,
                gatewayName: $this->getDisplayName()
            );
        }

        try {
            $response = $this->httpRequest('GET', 
                $this->config['base_url'] . '/payment/status/' . $transaction->external_transaction_id,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->config['api_key'],
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            if ($response['success']) {
                $status = $response['data']['status'] ?? 'unknown';
                
                match ($status) {
                    'completed', 'success' => $transaction->markAsCompleted(),
                    'failed' => $transaction->markAsFailed($response['data']['message'] ?? 'Payment failed'),
                    'refunded' => $transaction->markAsRefunded(),
                    default => null, // Keep current status
                };
            }

            $this->log('info', 'Payment verification result', [
                'transaction_id' => $transaction->id,
                'external_id' => $transaction->external_transaction_id,
                'status' => $transaction->status,
            ]);
        } catch (\Exception $e) {
            $this->log('error', 'Payment verification failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $transaction->fresh();
    }

    /**
     * Refund a Vodafone Cash payment.
     */
    public function refund(PaymentTransaction $transaction, ?float $amount = null, ?string $reason = null): PaymentTransaction
    {
        if (!$transaction->isCompleted()) {
            throw new PaymentException(
                'Only completed transactions can be refunded.',
                400,
                gatewayName: $this->getDisplayName()
            );
        }

        $refundAmount = $amount ?? $transaction->amount;
        
        if ($refundAmount > $transaction->amount) {
            throw new PaymentException(
                'Refund amount cannot exceed the original transaction amount.',
                422,
                gatewayName: $this->getDisplayName()
            );
        }

        try {
            $response = $this->httpRequest('POST', $this->config['base_url'] . '/payment/refund', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'transaction_id' => $transaction->external_reference,
                    'merchant_id' => $this->config['merchant_id'],
                    'original_transaction_id' => $transaction->external_transaction_id,
                    'amount' => $this->formatAmount($refundAmount),
                    'reason' => $reason ?? 'Customer requested refund',
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
                    'refund_amount' => $refundAmount,
                ]);
            } else {
                throw PaymentException::refundFailed(
                    $this->getDisplayName(),
                    $response['data']['message'] ?? 'Refund failed'
                );
            }
        } catch (\Exception $e) {
            if ($e instanceof PaymentException) throw $e;
            
            throw PaymentException::refundFailed(
                $this->getDisplayName(),
                $e->getMessage()
            );
        }

        return $transaction->fresh();
    }

    /**
     * Handle webhook callback from Vodafone Cash.
     */
    public function handleWebhook(array $payload): ?PaymentTransaction
    {
        $transactionId = $payload['transaction_id'] ?? $payload['reference'] ?? null;
        
        if (!$transactionId) {
            $this->log('warning', 'Webhook received without transaction ID');
            return null;
        }

        $transaction = PaymentTransaction::where('external_reference', $transactionId)
            ->orWhere('external_transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            $this->log('warning', 'Webhook received for unknown transaction', [
                'transaction_id' => $transactionId,
            ]);
            return null;
        }

        $status = $payload['status'] ?? 'unknown';
        
        match ($status) {
            'completed', 'success' => $transaction->markAsCompleted($payload['transaction_id'] ?? null),
            'failed' => $transaction->markAsFailed($payload['message'] ?? 'Payment failed via webhook'),
            'refunded' => $transaction->markAsRefunded(),
            default => $this->log('info', 'Unhandled webhook status', ['status' => $status]),
        };

        // Update webhook data
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
     * Get external transaction status from Vodafone Cash.
     */
    public function getExternalTransactionStatus(string $externalTransactionId): array
    {
        try {
            $response = $this->httpRequest('GET', 
                $this->config['base_url'] . '/payment/status/' . $externalTransactionId,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->config['api_key'],
                    ],
                ]
            );

            return [
                'success' => $response['success'],
                'status' => $response['data']['status'] ?? 'unknown',
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
     * Generate QR Code for Vodafone Cash payment.
     */
    public function generateQrCode(PaymentTransaction $transaction): ?string
    {
        if (!$transaction->external_reference) {
            return null;
        }

        try {
            $response = $this->httpRequest('POST', $this->config['base_url'] . '/payment/qr-code', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'transaction_id' => $transaction->external_reference,
                    'amount' => $this->formatAmount($transaction->amount),
                    'merchant_id' => $this->config['merchant_id'],
                ],
            ]);

            if ($response['success'] && isset($response['data']['qr_code_url'])) {
                $transaction->update([
                    'qr_code_url' => $response['data']['qr_code_url'],
                    'qr_code_data' => $response['data']['qr_code_data'] ?? null,
                ]);

                return $response['data']['qr_code_url'];
            }
        } catch (\Exception $e) {
            $this->log('error', 'QR Code generation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}