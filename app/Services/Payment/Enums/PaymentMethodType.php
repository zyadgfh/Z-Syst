<?php

namespace App\Services\Payment\Enums;

enum PaymentMethodType: string
{
    // International Gateways
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case RAZORPAY = 'razorpay';
    case PAYSTACK = 'paystack';
    case FLUTTERWAVE = 'flutterwave';
    case MOLLIE = 'mollie';
    case MERCADO = 'mercado';
    case INSTAMOJO = 'instamojo';
    case SSLCOMMERZ = 'sslcommerz';
    case TAP = 'tap';
    case THAWANI = 'thawani';
    case PHONEPE = 'phonepe';
    case PAYTM = 'paytm';
    case TOYYIBPAY = 'toyyibpay';

    // Egyptian Mobile Wallets
    case VODAFONE_CASH = 'vodafone_cash';
    case ORANGE_CASH = 'orange_cash';
    case ETISALAT_CASH = 'etisalat_cash';
    case INSTAPAY = 'instapay';

    // Local Payment Methods
    case CASH = 'cash';
    case BNPL = 'bnpl'; // Buy Now Pay Later (Ajel)
    case MIXED = 'mixed'; // Mixed payment methods

    // Card Payments
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';

    public function label(): string
    {
        return match($this) {
            self::STRIPE => 'Stripe',
            self::PAYPAL => 'PayPal',
            self::RAZORPAY => 'Razorpay',
            self::PAYSTACK => 'Paystack',
            self::FLUTTERWAVE => 'Flutterwave',
            self::MOLLIE => 'Mollie',
            self::MERCADO => 'Mercado Pago',
            self::INSTAMOJO => 'Instamojo',
            self::SSLCOMMERZ => 'SSLCommerz',
            self::TAP => 'Tap',
            self::THAWANI => 'Thawani',
            self::PHONEPE => 'PhonePe',
            self::PAYTM => 'Paytm',
            self::TOYYIBPAY => 'Toyyibpay',
            self::VODAFONE_CASH => 'Vodafone Cash',
            self::ORANGE_CASH => 'Orange Cash',
            self::ETISALAT_CASH => 'Etisalat Cash',
            self::INSTAPAY => 'InstaPay',
            self::CASH => 'Cash / نقدي',
            self::BNPL => 'BNPL / أجل',
            self::MIXED => 'Mixed Payment',
            self::CREDIT_CARD => 'Credit Card',
            self::DEBIT_CARD => 'Debit Card',
        };
    }

    public function isLocal(): bool
    {
        return in_array($this, [
            self::VODAFONE_CASH,
            self::ORANGE_CASH,
            self::ETISALAT_CASH,
            self::INSTAPAY,
            self::CASH,
            self::BNPL,
            self::MIXED,
        ]);
    }

    public function isEgyptian(): bool
    {
        return in_array($this, [
            self::VODAFONE_CASH,
            self::ORANGE_CASH,
            self::ETISALAT_CASH,
            self::INSTAPAY,
            self::CASH,
            self::BNPL,
        ]);
    }

    public function isMobileWallet(): bool
    {
        return in_array($this, [
            self::VODAFONE_CASH,
            self::ORANGE_CASH,
            self::ETISALAT_CASH,
            self::INSTAPAY,
        ]);
    }

    public function requiresQrCode(): bool
    {
        return in_array($this, [
            self::VODAFONE_CASH,
            self::ORANGE_CASH,
            self::ETISALAT_CASH,
            self::INSTAPAY,
        ]);
    }

    public function supportsRefund(): bool
    {
        return match($this) {
            self::CASH => true,
            self::BNPL => true,
            self::MIXED => false,
            default => true,
        };
    }

    public function requiresOnline(): bool
    {
        return match($this) {
            self::CASH => false,
            self::BNPL => false,
            default => true,
        };
    }
}