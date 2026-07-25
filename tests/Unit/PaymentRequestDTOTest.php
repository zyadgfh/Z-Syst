<?php

namespace Tests\Unit;

use App\Services\Payment\DTOs\PaymentRequestDTO;
use App\Services\Payment\Enums\PaymentMethodType;
use Tests\TestCase;

class PaymentRequestDTOTest extends TestCase
{

    public function test_it_rejects_zero_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment amount must be greater than 0. Received: 0');

        PaymentRequestDTO::fromArray([
            'payment_method_type' => PaymentMethodType::CASH->value,
            'amount' => 0,
        ]);
    }

    public function test_it_rejects_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment amount must be greater than 0. Received: -10');

        PaymentRequestDTO::fromArray([
            'payment_method_type' => PaymentMethodType::CASH->value,
            'amount' => -10,
        ]);
    }
}
