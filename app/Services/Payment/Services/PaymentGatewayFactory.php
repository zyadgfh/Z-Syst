<?php

namespace App\Services\Payment\Services;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Gateways\PaymobGateway;
use App\Services\Payment\Gateways\VodafoneCashGateway;
use Illuminate\Support\Facades\Log;

/**
 * Payment Gateway Factory
 * 
 * مصنع لإنشاء نسخ من البوابات المختلفة
 */
class PaymentGatewayFactory
{
    protected array $gateways = [];

    public function __construct()
    {
        $this->registerGateways();
    }

    /**
     * Register all available payment gateways
     */
    protected function registerGateways(): void
    {
        $this->gateways = [
            // Main Paymob aggregator - supports all Egyptian wallets
            'paymob' => PaymobGateway::class,
            
            // Individual gateways (using Paymob as backend)
            'vodafone_cash' => VodafoneCashGateway::class,
            'orange_cash' => \App\Services\Payment\Gateways\OrangeCashGateway::class,
            'etisalat_cash' => \App\Services\Payment\Gateways\EtisalatCashGateway::class,
            'instapay' => \App\Services\Payment\Gateways\InstaPayGateway::class,
            'we_pay' => \App\Services\Payment\Gateways\WePayGateway::class,
        ];
    }

    /**
     * Create a gateway instance by gateway name
     */
    public function make(string $gateway): PaymentGatewayInterface
    {
        $gatewayClass = $this->gateways[$gateway] ?? null;

        if (!$gatewayClass) {
            throw PaymentException::gatewayNotAvailable($gateway);
        }

        $config = $this->getGatewayConfig($gateway);

        return app()->make($gatewayClass, ['config' => $config]);
    }

    /**
     * Get gateway by payment method type
     */
    public function getByMethod(PaymentMethodType|string $methodType): PaymentGatewayInterface
    {
        $type = $methodType instanceof PaymentMethodType ? $methodType->value : $methodType;

        // All Egyptian methods use Paymob as the aggregator
        return $this->make('paymob');
    }

    /**
     * Get gateway configuration
     */
    protected function getGatewayConfig(string $gateway): array
    {
        $config = config("payments.gateways.{$gateway}", []);
        
        if (empty($config)) {
            // Try to get from services config
            $config = config("services.{$gateway}", []);
        }

        return $config;
    }

    /**
     * Check if gateway is available
     */
    public function isAvailable(string $gateway): bool
    {
        try {
            $instance = $this->make($gateway);
            return $instance->isAvailable();
        } catch (\Exception $e) {
            Log::error('Gateway availability check failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get all available gateway names
     */
    public function getAvailableGateways(): array
    {
        $available = [];

        foreach (array_keys($this->gateways) as $gateway) {
            if ($this->isAvailable($gateway)) {
                $available[] = $gateway;
            }
        }

        return $available;
    }

    /**
     * Get supported payment methods for a gateway
     */
    public function getSupportedMethods(string $gateway): array
    {
        try {
            $instance = $this->make($gateway);
            return $instance->getSupportedMethods();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Register a custom gateway at runtime
     */
    public function register(string $gateway, string $gatewayClass): void
    {
        $this->gateways[$gateway] = $gatewayClass;
    }
}