<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Enums\PaymentMethodType;

/**
 * We Pay Gateway
 * 
 * محفظة WE الإلكترونية
 * يعتمد على Paymob كخلفية
 */
class WePayGateway extends PaymobGateway
{
    public function getMethodType(): PaymentMethodType
    {
        return PaymentMethodType::WE_PAY;
    }

    public function getDisplayName(): string
    {
        return 'We Pay';
    }

    protected function getConfigPrefix(): string
    {
        return 'we_pay';
    }
}