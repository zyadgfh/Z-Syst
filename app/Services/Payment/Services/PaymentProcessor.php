<?php

namespace App\Services\Payment\Services;

use App\Models\Company;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Gateways\CashPaymentGateway;
use App\Services\Payment\Gateways\VodafoneCashGateway;
use App\Services\Payment\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;

/**
 * Payment Processor - Main payment orchestration service
 * 
 * مسؤول عن توجيه المدفوعات إلى البوابة المناسبة
 * يدعم: الدفع الفردي والمختلط (Mixed Payment)
 */
class PaymentProcessor
{
    protected array $gateways = [];
    protected ?Company $company = null;

    public function __construct(?Company $company = null)
    {
        $this->company = $company;
        $this->registerGateways();
    }

    /**
     * Register all available payment gateways.
     */
    protected function registerGateways(): void
    {
        $this->gateways = [
            // Egyptian Mobile Wallets
            PaymentMethodType::VODAFONE_CASH->value => VodafoneCashGateway::class,
            
            // Local Payments
            PaymentMethodType::CASH->value => CashPaymentGateway::class,
            
            // More gateways will be registered here as they are implemented:
            // PaymentMethodType::ORANGE_CASH->value => OrangeCashGateway::class,
            // PaymentMethodType::ETISALAT_CASH->value => EtisalatCashGateway::class,
            // PaymentMethodType::INSTAPAY->value => InstaPayGateway::class,
            // PaymentMethodType::BNPL->value => BNPLPaymentGateway::class,
        ];
    }

    /**
     * Get a payment gateway instance by method type.
     */
    public function getGateway(PaymentMethodType|string $methodType): PaymentGatewayInterface
    {
        $type = $methodType instanceof PaymentMethodType ? $methodType->value : $methodType;
        
        if (!isset($this->gateways[$type])) {
            throw PaymentException::gatewayNotAvailable($type);
        }

        $gatewayClass = $this->gateways[$type];
        
        return app()->make($gatewayClass, ['company' => $this->company]);
    }

    /**
     * Process a single payment through a specific gateway.
     */
    public function processSinglePayment(PaymentMethodType $methodType, array $data): PaymentTransaction
    {
        $gateway = $this->getGateway($methodType);
        
        if (!$gateway->isAvailable()) {
            throw PaymentException::invalidConfiguration($gateway->getDisplayName());
        }

        // Initiate the payment
        $transaction = $gateway->initiate($data);

        // Process if needed (some gateways need a separate process step)
        if ($transaction->isPending()) {
            $transaction = $gateway->process($transaction, $data);
        }

        return $transaction;
    }

    /**
     * Process a mixed payment (multiple payment methods for one sale).
     * 
     * مثال: 300 جنيه كاش + 200 جنيه فودافون كاش = 500 جنيه إجمالي
     */
    public function processMixedPayment(array $payments, array $commonData = []): array
    {
        $transactions = [];
        $totalProcessed = 0;
        $expectedTotal = $commonData['total_amount'] ?? array_sum(array_column($payments, 'amount'));

        foreach ($payments as $payment) {
            $methodType = $payment['method_type'] instanceof PaymentMethodType 
                ? $payment['method_type'] 
                : PaymentMethodType::from($payment['method_type']);

            $paymentData = array_merge($commonData, $payment, [
                'reference_id' => $commonData['reference_id'] ?? null,
                'reference_type' => $commonData['reference_type'] ?? null,
            ]);

            try {
                $transaction = $this->processSinglePayment($methodType, $paymentData);
                $transactions[] = $transaction;
                $totalProcessed += $transaction->amount;
            } catch (\Exception $e) {
                // If one payment fails, rollback the processed ones
                foreach ($transactions as $processedTx) {
                    try {
                        $gateway = $this->getGateway($processedTx->payment_method_type);
                        $gateway->refund($processedTx, null, 'Mixed payment rollback due to partial failure');
                    } catch (\Exception $rollbackError) {
                        Log::error('Failed to rollback transaction', [
                            'transaction_id' => $processedTx->id,
                            'error' => $rollbackError->getMessage(),
                        ]);
                    }
                }

                throw new PaymentException(
                    "Mixed payment failed at {$methodType->label()}: {$e->getMessage()}",
                    400,
                    $e
                );
            }
        }

        return [
            'success' => true,
            'transactions' => $transactions,
            'total_amount' => $totalProcessed,
            'expected_total' => $expectedTotal,
            'is_complete' => abs($totalProcessed - $expectedTotal) < 0.01,
        ];
    }

    /**
     * Verify a payment transaction.
     */
    public function verifyTransaction(PaymentTransaction $transaction): PaymentTransaction
    {
        try {
            $gateway = $this->getGateway($transaction->payment_method_type);
            return $gateway->verify($transaction);
        } catch (\Exception $e) {
            Log::error('Transaction verification failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Refund a payment transaction.
     */
    public function refundTransaction(PaymentTransaction $transaction, ?float $amount = null, ?string $reason = null): PaymentTransaction
    {
        try {
            $gateway = $this->getGateway($transaction->payment_method_type);
            return $gateway->refund($transaction, $amount, $reason);
        } catch (\Exception $e) {
            Log::error('Transaction refund failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle webhook callbacks for all gateways.
     * Tries to match the webhook to the appropriate gateway.
     */
    public function handleWebhook(string $gatewayType, array $payload): ?PaymentTransaction
    {
        try {
            $methodType = PaymentMethodType::from($gatewayType);
            $gateway = $this->getGateway($methodType);
            return $gateway->handleWebhook($payload);
        } catch (\Exception $e) {
            Log::error('Webhook handling failed', [
                'gateway' => $gatewayType,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get all available payment methods.
     */
    public function getAvailableMethods(): array
    {
        $methods = [];
        
        foreach ($this->gateways as $type => $gatewayClass) {
            try {
                $gateway = app()->make($gatewayClass, ['company' => $this->company]);
                if ($gateway->isAvailable()) {
                    $methods[] = [
                        'type' => $type,
                        'name' => $gateway->getDisplayName(),
                        'method_type' => PaymentMethodType::from($type),
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $methods;
    }

    /**
     * Register a custom gateway at runtime.
     */
    public function registerGateway(string $methodType, string $gatewayClass): void
    {
        $this->gateways[$methodType] = $gatewayClass;
    }

    /**
     * Set the company context for payment processing.
     */
    public function setCompany(?Company $company): self
    {
        $this->company = $company;
        return $this;
    }
}