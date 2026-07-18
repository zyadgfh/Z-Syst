<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Enums\PaymentMethodType;

/**
 * Orange Cash Payment Gateway
 * 
 * يعتمد على Paymob كخلفية
 * يدعم: USSD Push, QR Code, Webhook, Refunds
 */
class OrangeCashGateway extends PaymobGateway
{
    public function getMethodType(): PaymentMethodType
    {
        return PaymentMethodType::ORANGE_CASH;
    }

    public function getDisplayName(): string
    {
        return 'Orange Cash';
    }

    protected function getConfigPrefix(): string
    {
        return 'orange_cash';
    }
}