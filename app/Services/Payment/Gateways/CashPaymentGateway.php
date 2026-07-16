<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Enums\TransactionStatus;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Models\PaymentTransaction;

/**
 * Cash Payment Gateway
 * 
 * نظام الدفع النقدي مع إدارة الصندوق (Cash Drawer)
 * يدعم: تسجيل الدفع النقدي، إدارة الصندوق، تقارير يومية، تسوية الصندوق
 */
class CashPaymentGateway extends BaseGateway
{
    protected function getConfigPrefix(): string
    {
        return 'cash';
    }

    protected function loadConfig(): void
    {
        $this->config = [
            'min_amount' => config('services.cash.min_amount', 0.25),
            'max_amount' => config('services.cash.max_amount', 100000),
            'require_cashier' => config('services.cash.require_cashier', true),
            'require_receipt' => config('services.cash.require_receipt', true),
        ];
    }

    public function getMethodType(): PaymentMethodType
    {
        return PaymentMethodType::CASH;
    }

    public function getDisplayName(): string
    {
        return 'Cash / نقدي';
    }

    public function getRequiredConfig(): array
    {
        return [];
    }

    /**
     * Initiate a cash payment.
     * Simply records the cash transaction with the given amount.
     */
    public function initiate(array $data): PaymentTransaction
    {
        $amount = $data['amount'];
        $this->validateAmount($amount);

        // Validate cash received if provided
        $cashReceived = $data['cash_received'] ?? $amount;
        if ($cashReceived < $amount) {
            throw new PaymentException(
                'Cash received is less than the payment amount.',
                422,
                gatewayName: $this->getDisplayName()
            );
        }

        $changeDue = $cashReceived - $amount;

        $transaction = $this->createTransaction([
            'amount' => $amount,
            'currency' => $data['currency'] ?? 'EGP',
            'reference_id' => $data['reference_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'description' => $data['description'] ?? 'Cash Payment',
            'metadata' => array_merge($data['metadata'] ?? [], [
                'cash_received' => $cashReceived,
                'change_due' => $changeDue,
                'cashier_id' => $data['cashier_id'] ?? auth()->id(),
                'cashier_name' => $data['cashier_name'] ?? auth()->user()?->name,
                'cash_register_id' => $data['cash_register_id'] ?? null,
            ]),
            'user_id' => $data['user_id'] ?? auth()->id(),
        ]);

        // Cash payments are completed immediately
        $transaction->markAsCompleted();

        $this->log('info', 'Cash payment completed', [
            'transaction_id' => $transaction->id,
            'amount' => $amount,
            'cash_received' => $cashReceived,
            'change_due' => $changeDue,
        ]);

        return $transaction->fresh();
    }

    /**
     * Process - cash is already completed at initiation.
     */
    public function process(PaymentTransaction $transaction, array $data = []): PaymentTransaction
    {
        return $transaction;
    }

    /**
     * Verify - cash is always verified at completion.
     */
    public function verify(PaymentTransaction $transaction): PaymentTransaction
    {
        return $transaction;
    }

    /**
     * Refund a cash payment.
     * This records a cash refund from the register.
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

        $transaction->markAsRefunded($refundAmount, $reason);

        $this->log('info', 'Cash refund processed', [
            'transaction_id' => $transaction->id,
            'refund_amount' => $refundAmount,
        ]);

        return $transaction->fresh();
    }

    /**
     * Get external transaction status (not applicable for cash).
     */
    public function getExternalTransactionStatus(string $externalTransactionId): array
    {
        return [
            'success' => true,
            'status' => 'completed',
            'message' => 'Cash payments are always completed immediately.',
        ];
    }
}