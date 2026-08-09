<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;

class VodafoneCashGateway extends BasePaymentGateway
{
    public function getGatewayType(): string
    {
        return CompanyPaymentGateway::GATEWAY_VODAFONE_CASH;
    }

    public function getGatewayName(): string
    {
        return 'Vodafone Cash';
    }

    public function getRequiredConfigFields(): array
    {
        return [
            'merchant_id' => 'Merchant ID',
            'api_key' => 'API Key',
            'api_secret' => 'API Secret',
            'environment' => 'Environment (sandbox/production)',
        ];
    }

    public function validateConfig(array $config): bool
    {
        return ! empty($config['merchant_id']) &&
               ! empty($config['api_key']) &&
               ! empty($config['api_secret']);
    }

    public function processPayment(array $paymentData): PaymentTransaction
    {
        $transaction = $this->createTransaction($paymentData);

        try {
            $amount = $this->calculateTotalAmount($paymentData['amount']);
            $customerPhone = $paymentData['customer_phone'] ?? null;

            if (empty($customerPhone)) {
                throw new \Exception('Customer phone number is required for Vodafone Cash');
            }

            // Vodafone Cash API integration
            $response = $this->callVodafoneCashApi([
                'merchant_id' => $this->config['merchant_id'],
                'amount' => $amount,
                'customer_phone' => $customerPhone,
                'reference' => $transaction->internal_reference,
                'currency' => 'EGP',
            ]);

            if ($this->isSuccessfulResponse($response)) {
                $this->handlePaymentSuccess(
                    $transaction,
                    $response['transaction_id'] ?? $response['reference'],
                    $response
                );
            } else {
                throw new \Exception($response['message'] ?? 'Payment failed');
            }

        } catch (\Exception $e) {
            $this->handlePaymentError($transaction, $e->getMessage());
        }

        return $transaction;
    }

    public function verifyPayment(string $referenceId): array
    {
        try {
            $response = $this->callVodafoneCashStatusApi([
                'merchant_id' => $this->config['merchant_id'],
                'reference' => $referenceId,
            ]);

            return [
                'success' => $this->isSuccessfulResponse($response),
                'status' => $response['status'] ?? 'unknown',
                'amount' => $response['amount'] ?? null,
                'data' => $response,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function processRefund(PaymentTransaction $transaction, ?float $amount = null): array
    {
        try {
            $refundAmount = $amount ?? $transaction->amount;

            $response = $this->callVodafoneCashRefundApi([
                'merchant_id' => $this->config['merchant_id'],
                'reference' => $transaction->reference_id,
                'amount' => $refundAmount,
            ]);

            if ($this->isSuccessfulResponse($response)) {
                $transaction->markAsRefunded($response);

                return [
                    'success' => true,
                    'refund_id' => $response['refund_id'] ?? null,
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Refund failed',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function callVodafoneCashApi(array $data): array
    {
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://api.vodafonecash.com.eg'
            : 'https://sandbox.vodafonecash.com.eg';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/v1/payments", $data);

        return $response->json() ?? [];
    }

    private function callVodafoneCashStatusApi(array $data): array
    {
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://api.vodafonecash.com.eg'
            : 'https://sandbox.vodafonecash.com.eg';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->config['api_key'],
        ])->get("{$baseUrl}/v1/payments/status", $data);

        return $response->json() ?? [];
    }

    private function callVodafoneCashRefundApi(array $data): array
    {
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://api.vodafonecash.com.eg'
            : 'https://sandbox.vodafonecash.com.eg';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/v1/refunds", $data);

        return $response->json() ?? [];
    }

    private function isSuccessfulResponse(array $response): bool
    {
        return isset($response['status']) && in_array($response['status'], ['success', 'completed', 'paid']);
    }
}
