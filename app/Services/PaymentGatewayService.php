<?php

namespace App\Services;

use App\Exceptions\PaymentException;
use App\Models\CompanyPaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\PaymentGateways\BankCardGateway;
use App\Services\PaymentGateways\BasePaymentGateway;
use App\Services\PaymentGateways\CashGateway;
use App\Services\PaymentGateways\FawryGateway;
use App\Services\PaymentGateways\InstaPayGateway;
use App\Services\PaymentGateways\OrangeCashGateway;
use App\Services\PaymentGateways\VodafoneCashGateway;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    /**
     * Get the appropriate gateway instance based on type.
     */
    public function getGateway(CompanyPaymentGateway $gatewayConfig): BasePaymentGateway
    {
        try {
            return match ($gatewayConfig->gateway_type) {
                CompanyPaymentGateway::GATEWAY_VODAFONE_CASH => new VodafoneCashGateway($gatewayConfig),
                CompanyPaymentGateway::GATEWAY_BANK_CARD => new BankCardGateway($gatewayConfig),
                CompanyPaymentGateway::GATEWAY_FAWRY => new FawryGateway($gatewayConfig),
                CompanyPaymentGateway::GATEWAY_ORANGE_CASH => new OrangeCashGateway($gatewayConfig),
                CompanyPaymentGateway::GATEWAY_INSTAPAY => new InstaPayGateway($gatewayConfig),
                CompanyPaymentGateway::GATEWAY_CASH => new CashGateway($gatewayConfig),
                default => throw PaymentException::gatewayNotFound($gatewayConfig->gateway_type),
            };
        } catch (\Exception $e) {
            Log::error('Failed to create gateway instance', [
                'gateway_type' => $gatewayConfig->gateway_type,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get available gateways for a company.
     */
    public function getAvailableGateways(int $companyId, ?int $branchId = null): Collection
    {
        $query = CompanyPaymentGateway::byCompany($companyId)->active();

        if ($branchId) {
            // Get branch-specific gateways first, then company-level as fallback
            $branchGateways = (clone $query)->byBranch($branchId)->get();
            if ($branchGateways->isNotEmpty()) {
                return $branchGateways;
            }
        }

        return $query->whereNull('branch_id')->get();
    }

    /**
     * Get available gateways for a company grouped by type.
     */
    public function getAvailableGatewaysGrouped(int $companyId, ?int $branchId = null): array
    {
        $gateways = $this->getAvailableGateways($companyId, $branchId);

        return $gateways->groupBy('gateway_type')->map(function ($group) {
            return $group->first();
        })->toArray();
    }

    /**
     * Process a payment through a specific gateway.
     */
    public function processPayment(int $gatewayId, array $paymentData): PaymentTransaction
    {
        $gatewayConfig = CompanyPaymentGateway::findOrFail($gatewayId);

        if (! $gatewayConfig->is_active) {
            throw PaymentException::gatewayNotActive();
        }

        $gateway = $this->getGateway($gatewayConfig);

        try {
            return $gateway->processPayment($paymentData);
        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'gateway_id' => $gatewayId,
                'error' => $e->getMessage(),
            ]);
            throw PaymentException::paymentProcessingFailed($e->getMessage());
        }
    }

    /**
     * Process a payment using the first available gateway.
     */
    public function processPaymentWithDefaultGateway(int $companyId, array $paymentData, ?int $branchId = null): PaymentTransaction
    {
        $gateways = $this->getAvailableGateways($companyId, $branchId);

        if ($gateways->isEmpty()) {
            throw PaymentException::noAvailableGateways();
        }

        $gatewayConfig = $gateways->sortBy('sort_order')->first();

        return $this->processPayment($gatewayConfig->id, $paymentData);
    }

    /**
     * Verify a payment status.
     */
    public function verifyPayment(int $gatewayId, string $referenceId): array
    {
        $gatewayConfig = CompanyPaymentGateway::findOrFail($gatewayId);
        $gateway = $this->getGateway($gatewayConfig);

        return $gateway->verifyPayment($referenceId);
    }

    /**
     * Process a refund.
     */
    public function processRefund(int $transactionId, ?float $amount = null): array
    {
        $transaction = PaymentTransaction::findOrFail($transactionId);

        if ($transaction->status !== PaymentTransaction::STATUS_COMPLETED) {
            throw PaymentException::invalidTransactionStatus(
                PaymentTransaction::STATUS_COMPLETED,
                $transaction->status
            );
        }

        if ($transaction->gateway_id) {
            $gatewayConfig = $transaction->gateway;
            $gateway = $this->getGateway($gatewayConfig);

            try {
                return $gateway->processRefund($transaction, $amount);
            } catch (\Exception $e) {
                Log::error('Refund processing failed', [
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage(),
                ]);
                throw PaymentException::refundFailed($e->getMessage());
            }
        }

        $cashGateway = new CashGateway($transaction->gateway ?? new CompanyPaymentGateway([
            'gateway_type' => CompanyPaymentGateway::GATEWAY_CASH,
            'config_data' => [],
        ]));

        return $cashGateway->processRefund($transaction, $amount);
    }

    /**
     * Create or update a payment gateway configuration.
     */
    public function upsertGatewayConfig(array $data): CompanyPaymentGateway
    {
        $data['config_data'] = $data['config_data'] ?? [];

        return CompanyPaymentGateway::updateOrCreate(
            [
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'gateway_type' => $data['gateway_type'],
            ],
            [
                'is_active' => $data['is_active'] ?? true,
                'config_data' => $data['config_data'],
                'branch_config_data' => $data['branch_config_data'] ?? null,
                'transaction_fee' => $data['transaction_fee'] ?? 0,
                'transaction_fee_type' => $data['transaction_fee_type'] ?? 'percentage',
                'sort_order' => $data['sort_order'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]
        );
    }

    /**
     * Validate gateway configuration.
     */
    public function validateGatewayConfig(string $gatewayType, array $config): array
    {
        try {
            $dummyGateway = $this->createDummyGateway($gatewayType);
            $isValid = $dummyGateway->validateConfig($config);

            return [
                'valid' => $isValid,
                'message' => $isValid ? 'Configuration is valid' : 'Configuration is invalid',
                'required_fields' => $dummyGateway->getRequiredConfigFields(),
            ];
        } catch (PaymentException $e) {
            return [
                'valid' => false,
                'message' => $e->getMessage(),
                'required_fields' => [],
            ];
        } catch (\Exception $e) {
            Log::error('Gateway configuration validation failed', [
                'gateway_type' => $gatewayType,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'message' => 'Validation error occurred',
                'required_fields' => [],
            ];
        }
    }

    /**
     * Get required configuration fields for a gateway type.
     */
    public function getRequiredConfigFields(string $gatewayType): array
    {
        try {
            $dummyGateway = $this->createDummyGateway($gatewayType);

            return $dummyGateway->getRequiredConfigFields();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Create a dummy gateway instance for validation.
     */
    private function createDummyGateway(string $gatewayType): BasePaymentGateway
    {
        $dummyConfig = new CompanyPaymentGateway([
            'gateway_type' => $gatewayType,
            'config_data' => [],
        ]);

        return $this->getGateway($dummyConfig);
    }

    /**
     * Get transaction statistics for a company.
     */
    public function getTransactionStats(int $companyId, ?int $branchId = null, array $filters = []): array
    {
        $query = PaymentTransaction::byCompany($companyId);

        if ($branchId) {
            $query->byBranch($branchId);
        }

        if (isset($filters['gateway_type'])) {
            $query->byGatewayType($filters['gateway_type']);
        }

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['transaction_type'])) {
            $query->byType($filters['transaction_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $transactions = $query->get();

        return [
            'total_count' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'completed_count' => $transactions->where('status', PaymentTransaction::STATUS_COMPLETED)->count(),
            'completed_amount' => $transactions->where('status', PaymentTransaction::STATUS_COMPLETED)->sum('amount'),
            'pending_count' => $transactions->where('status', PaymentTransaction::STATUS_PENDING)->count(),
            'failed_count' => $transactions->where('status', PaymentTransaction::STATUS_FAILED)->count(),
            'refunded_count' => $transactions->where('status', PaymentTransaction::STATUS_REFUNDED)->count(),
            'by_gateway_type' => $transactions->groupBy('gateway_type')->map(fn ($group) => [
                'count' => $group->count(),
                'amount' => $group->sum('amount'),
            ])->toArray(),
            'by_transaction_type' => $transactions->groupBy('transaction_type')->map(fn ($group) => [
                'count' => $group->count(),
                'amount' => $group->sum('amount'),
            ])->toArray(),
        ];
    }

    /**
     * Deactivate a payment gateway.
     */
    public function deactivateGateway(int $gatewayId): bool
    {
        $gateway = CompanyPaymentGateway::findOrFail($gatewayId);
        $gateway->is_active = false;

        return $gateway->save();
    }

    /**
     * Activate a payment gateway.
     */
    public function activateGateway(int $gatewayId): bool
    {
        $gateway = CompanyPaymentGateway::findOrFail($gatewayId);
        $gateway->is_active = true;

        return $gateway->save();
    }
}
