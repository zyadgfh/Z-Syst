<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Enums\PaymentMethodType;

/**
 * InstaPay Gateway
 * 
 * الشبكة الفورية للدفع (Instant Payment Network)
 * يدعم QR Code + Reference Number
 * يعتمد على Paymob كخلفية
 */
class InstaPayGateway extends PaymobGateway
{
    public function getMethodType(): PaymentMethodType
    {
        return PaymentMethodType::INSTAPAY;
    }

    public function getDisplayName(): string
    {
        return 'InstaPay';
    }

    protected function getConfigPrefix(): string
    {
        return 'instapay';
    }

    /**
     * InstaPay generates QR code automatically
     */
    public function generateQrCode($transaction): ?string
    {
        if (!$transaction->external_reference) {
            return null;
        }

        // InstaPay QR code contains the reference number
        // Customers scan this to complete payment
        return $this->generateInstaPayQrCode($transaction);
    }

    /**
     * Generate QR code data for InstaPay
     */
    protected function generateInstaPayQrCode($transaction): string
    {
        $qrData = [
            'merchant_id' => $this->config['merchant_id'],
            'reference' => $transaction->external_reference,
            'amount' => $transaction->amount,
        ];

        return json_encode($qrData);
    }
}