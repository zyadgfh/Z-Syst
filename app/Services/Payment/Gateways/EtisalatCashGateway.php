<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Enums\PaymentMethodType;

/**
 * Etisalat Cash Payment Gateway
 * 
 * يعتمد على Paymob كخلفية
 * يدعم: USSD Push, QR Code, Webhook, Refunds
 */
class EtisalatCashGateway extends PaymobGateway
{
    public function getMethodType(): PaymentMethodType
    {
        return PaymentMethodType::ETISALAT_CASH;
    }

    public function getDisplayName(): string
    {
        return 'Etisalat Cash';
    }

    protected function getConfigPrefix(): string
    {
        return 'etisalat_cash';
    }
}