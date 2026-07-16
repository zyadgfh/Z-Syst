<?php

namespace App\Services\Payment\Contracts;

use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Models\PaymentTransaction;

interface PaymentGatewayInterface
{
    /**
     * Get the payment method type this gateway handles.
     */
    public function getMethodType(): PaymentMethodType;

    /**
     * Get the display name of this payment gateway.
     */
    public function getDisplayName(): string;

    /**
     * Initialize a payment transaction.
     *
     * @param array $data Transaction data including amount, currency, customer info, etc.
     * @return PaymentTransaction
     */
    public function initiate(array $data): PaymentTransaction;

    /**
     * Process/confirm a payment.
     *
     * @param PaymentTransaction $transaction
     * @param array $data Additional data needed for processing
     * @return PaymentTransaction
     */
    public function process(PaymentTransaction $transaction, array $data = []): PaymentTransaction;

    /**
     * Verify a payment transaction status.
     *
     * @param PaymentTransaction $transaction
     * @return PaymentTransaction
     */
    public function verify(PaymentTransaction $transaction): PaymentTransaction;

    /**
     * Process a refund for a transaction.
     *
     * @param PaymentTransaction $transaction
     * @param float|null $amount Amount to refund (null for full refund)
     * @param string|null $reason Reason for refund
     * @return PaymentTransaction
     */
    public function refund(PaymentTransaction $transaction, ?float $amount = null, ?string $reason = null): PaymentTransaction;

    /**
     * Check if the gateway is configured and available.
     */
    public function isAvailable(): bool;

    /**
     * Get the gateway configuration requirements.
     *
     * @return array List of required configuration keys
     */
    public function getRequiredConfig(): array;

    /**
     * Validate the gateway configuration.
     */
    public function validateConfig(): bool;

    /**
     * Handle webhook callback from the payment gateway.
     *
     * @param array $payload The webhook payload
     * @return PaymentTransaction|null
     */
    public function handleWebhook(array $payload): ?PaymentTransaction;

    /**
     * Get the status of a transaction from the external gateway.
     *
     * @param string $externalTransactionId
     * @return array
     */
    public function getExternalTransactionStatus(string $externalTransactionId): array;

    /**
     * Generate a payment QR code if supported.
     *
     * @param PaymentTransaction $transaction
     * @return string|null URL or base64 of QR code
     */
    public function generateQrCode(PaymentTransaction $transaction): ?string;

    /**
     * Format amount according to gateway requirements.
     *
     * @param float $amount
     * @return mixed
     */
    public function formatAmount(float $amount): mixed;
}