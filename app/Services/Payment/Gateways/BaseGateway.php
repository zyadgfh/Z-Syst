<?php

namespace App\Services\Payment\Gateways;

use App\Models\Company;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Enums\TransactionStatus;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Models\PaymentTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class BaseGateway implements PaymentGatewayInterface
{
    protected ?Company $company = null;
    protected array $config = [];
    protected bool $isTestMode = false;

    public function __construct(?Company $company = null)
    {
        $this->company = $company;
        $this->loadConfig();
    }

    /**
     * Load gateway configuration from settings.
     */
    abstract protected function loadConfig(): void;

    /**
     * Get the gateway's configuration prefix for env/settings.
     */
    abstract protected function getConfigPrefix(): string;

    /**
     * Create a new payment transaction record.
     */
    protected function createTransaction(array $data): PaymentTransaction
    {
        $companyId = $this->company?->id ?? (app()->bound('tenant.company_id') ? app('tenant.company_id') : null);

        return PaymentTransaction::create([
            'company_id' => $companyId,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'payment_method_type' => $this->getMethodType()->value,
            'transaction_type' => $data['transaction_type'] ?? 'sale',
            'reference_id' => $data['reference_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'EGP',
            'fee' => $data['fee'] ?? 0,
            'net_amount' => ($data['amount'] ?? 0) - ($data['fee'] ?? 0),
            'status' => TransactionStatus::PENDING->value,
            'external_reference' => $data['external_reference'] ?? $this->generateReference(),
            'mobile_number' => $data['mobile_number'] ?? null,
            'wallet_provider' => $data['wallet_provider'] ?? null,
            'callback_url' => $data['callback_url'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'initiated_at' => Carbon::now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Generate a unique reference number for the transaction.
     */
    protected function generateReference(): string
    {
        $prefix = strtoupper(Str::substr($this->getMethodType()->name, 0, 3));
        return $prefix . '-' . strtoupper(Str::random(8)) . '-' . time();
    }

    /**
     * Log a payment event.
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        Log::$level("Payment [{$this->getDisplayName()}]: {$message}", $context);
    }

    /**
     * Validate the amount is positive and within limits.
     */
    protected function validateAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw PaymentException::invalidAmount($this->getDisplayName());
        }

        $minAmount = $this->config['min_amount'] ?? 1;
        $maxAmount = $this->config['max_amount'] ?? 1000000;

        if ($amount < $minAmount) {
            throw new PaymentException(
                "Minimum amount for {$this->getDisplayName()} is {$minAmount}.",
                422,
                gatewayName: $this->getDisplayName()
            );
        }

        if ($amount > $maxAmount) {
            throw new PaymentException(
                "Maximum amount for {$this->getDisplayName()} is {$maxAmount}.",
                422,
                gatewayName: $this->getDisplayName()
            );
        }
    }

    public function isAvailable(): bool
    {
        return $this->validateConfig();
    }

    public function validateConfig(): bool
    {
        foreach ($this->getRequiredConfig() as $key) {
            if (empty($this->config[$key])) {
                return false;
            }
        }
        return true;
    }

    public function getRequiredConfig(): array
    {
        return [];
    }

    public function formatAmount(float $amount): mixed
    {
        return round($amount, 2);
    }

    public function generateQrCode(PaymentTransaction $transaction): ?string
    {
        return null;
    }

    public function handleWebhook(array $payload): ?PaymentTransaction
    {
        return null;
    }

    public function setTestMode(bool $mode): self
    {
        $this->isTestMode = $mode;
        return $this;
    }

    public function isTestMode(): bool
    {
        return $this->isTestMode;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * Get a config value.
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Make an HTTP request to the payment gateway.
     */
    protected function httpRequest(string $method, string $url, array $options = []): array
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => $this->getConfig('timeout', 30),
                'verify' => !$this->isTestMode,
            ]);

            $response = $client->request($method, $url, $options);

            $body = json_decode($response->getBody()->getContents(), true);

            $this->log('info', "HTTP {$method} {$url} - Status: {$response->getStatusCode()}", [
                'response' => $body,
            ]);

            return [
                'success' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
                'status_code' => $response->getStatusCode(),
                'data' => $body,
                'raw' => $response->getBody()->getContents(),
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->log('error', "HTTP Request failed: {$e->getMessage()}", [
                'method' => $method,
                'url' => $url,
            ]);

            throw new PaymentException(
                "Communication with {$this->getDisplayName()} failed: {$e->getMessage()}",
                502,
                $e,
                $this->getDisplayName()
            );
        }
    }
}