<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentTransaction;

class CashGateway extends BasePaymentGateway
{
    public function getGatewayType(): string
    {
        return CompanyPaymentGateway::GATEWAY_CASH;
    }

    public function getGatewayName(): string
    {
        return 'Cash';
    }

    public function getRequiredConfigFields(): array
    {
        return [
            'require_verification' => 'Require Manager Verification',
            'allow_partial_payment' => 'Allow Partial Payment',
            'auto_complete' => 'Auto Complete on Receipt',
        ];
    }

    public function validateConfig(array $config): bool
    {
        return true; // Cash payment doesn't require external API credentials
    }

    public function processPayment(array $paymentData): PaymentTransaction
    {
        $transaction = $this->createTransaction($paymentData);

        try {
            $amount = $this->calculateTotalAmount($paymentData['amount']);
            $receivedAmount = $paymentData['received_amount'] ?? $amount;
            $change = $receivedAmount - $amount;

            if ($this->config['allow_partial_payment'] ?? false) {
                if ($receivedAmount < $amount) {
                    $transaction->metadata = array_merge($transaction->metadata ?? [], [
                        'partial_payment' => true,
                        'received_amount' => $receivedAmount,
                        'remaining_amount' => $amount - $receivedAmount,
                    ]);

                    if (! ($this->config['auto_complete'] ?? false)) {
                        $transaction->status = PaymentTransaction::STATUS_PENDING;
                        $transaction->save();

                        return $transaction;
                    }
                }
            } else {
                if ($receivedAmount < $amount) {
                    throw new \Exception('Insufficient cash amount');
                }
            }

            $transaction->metadata = array_merge($transaction->metadata ?? [], [
                'received_amount' => $receivedAmount,
                'change_amount' => max(0, $change),
                'verified_by' => $paymentData['verified_by'] ?? null,
                'payment_notes' => $paymentData['payment_notes'] ?? null,
            ]);

            $this->handlePaymentSuccess(
                $transaction,
                'CASH-'.$transaction->internal_reference,
                [
                    'payment_method' => 'cash',
                    'received_amount' => $receivedAmount,
                    'change' => max(0, $change),
                ]
            );

        } catch (\Exception $e) {
            $this->handlePaymentError($transaction, $e->getMessage());
        }

        return $transaction;
    }

    public function verifyPayment(string $referenceId): array
    {
        // Cash payments are typically verified immediately upon receipt
        return [
            'success' => true,
            'status' => 'completed',
            'message' => 'Cash payment verified',
        ];
    }

    public function processRefund(PaymentTransaction $transaction, ?float $amount = null): array
    {
        try {
            $refundAmount = $amount ?? $transaction->amount;

            // Cash refunds are manual processes
            $transaction->markAsRefunded([
                'refund_method' => 'cash',
                'refund_amount' => $refundAmount,
                'refund_notes' => 'Manual cash refund processed',
                'processed_by' => auth()->id() ?? null,
            ]);

            return [
                'success' => true,
                'refund_id' => 'CASH-REFUND-'.time(),
                'message' => 'Cash refund processed successfully',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate change for cash payment.
     */
    public function calculateChange(float $amount, float $receivedAmount): float
    {
        return max(0, $receivedAmount - $amount);
    }

    /**
     * Validate cash payment amount.
     */
    public function validateCashAmount(float $amount, float $receivedAmount): array
    {
        if ($this->config['allow_partial_payment'] ?? false) {
            if ($receivedAmount <= 0) {
                return [
                    'valid' => false,
                    'error' => 'Received amount must be greater than zero',
                ];
            }
        } else {
            if ($receivedAmount < $amount) {
                return [
                    'valid' => false,
                    'error' => 'Insufficient cash amount',
                ];
            }
        }

        return [
            'valid' => true,
            'change' => $this->calculateChange($amount, $receivedAmount),
        ];
    }
}
