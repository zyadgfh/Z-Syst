<?php

namespace App\Services\PaymentGateways;

use App\Models\CompanyPaymentGateway;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;

abstract class BasePaymentGateway
{
    protected CompanyPaymentGateway $gatewayConfig;

    protected array $config;

    /**
     * Initialize the gateway with configuration.
     */
    public function __construct(CompanyPaymentGateway $gatewayConfig)
    {
        $this->gatewayConfig = $gatewayConfig;
        $this->config = $gatewayConfig->effective_config;
    }

    /**
     * Get the gateway type identifier.
     */
    abstract public function getGatewayType(): string;

    /**
     * Get the gateway name.
     */
    abstract public function getGatewayName(): string;

    /**
     * Process a payment.
     */
    abstract public function processPayment(array $paymentData): PaymentTransaction;

    /**
     * Verify a payment status.
     */
    abstract public function verifyPayment(string $referenceId): array;

    /**
     * Process a refund.
     */
    abstract public function processRefund(PaymentTransaction $transaction, ?float $amount = null): array;

    /**
     * Validate the gateway configuration.
     */
    abstract public function validateConfig(array $config): bool;

    /**
     * Get required configuration fields.
     */
    abstract public function getRequiredConfigFields(): array;

    /**
     * Create a payment transaction record.
     */
    protected function createTransaction(array $data): PaymentTransaction
    {
        return PaymentTransaction::create([
            'company_id' => $this->gatewayConfig->company_id,
            'branch_id' => $this->gatewayConfig->branch_id,
            'gateway_id' => $this->gatewayConfig->id,
            'gateway_type' => $this->getGatewayType(),
            'transaction_type' => $data['transaction_type'] ?? PaymentTransaction::TYPE_SALE,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'EGP',
            'status' => PaymentTransaction::STATUS_PENDING,
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'processed_by' => $data['processed_by'] ?? null,
        ]);
    }

    /**
     * Calculate the total amount including fees.
     */
    protected function calculateTotalAmount(float $amount): float
    {
        $fee = $this->gatewayConfig->calculateFee($amount);

        return $amount + $fee;
    }

    /**
     * Log payment activity.
     */
    protected function logPayment(string $message, array $context = []): void
    {
        Log::info("Payment Gateway [{$this->getGatewayName()}]: {$message}", array_merge([
            'gateway_type' => $this->getGatewayType(),
            'company_id' => $this->gatewayConfig->company_id,
            'branch_id' => $this->gatewayConfig->branch_id,
        ], $context));
    }

    /**
     * Handle payment errors.
     */
    protected function handlePaymentError(PaymentTransaction $transaction, string $error): void
    {
        $transaction->markAsFailed($error);
        $this->logPayment('Payment failed', [
            'transaction_id' => $transaction->id,
            'error' => $error,
        ]);
    }

    /**
     * Handle successful payment.
     */
    protected function handlePaymentSuccess(PaymentTransaction $transaction, string $referenceId, array $paymentData = []): void
    {
        $transaction->markAsCompleted($referenceId, $paymentData);
        $this->logPayment('Payment completed', [
            'transaction_id' => $transaction->id,
            'reference_id' => $referenceId,
        ]);
    }
}
