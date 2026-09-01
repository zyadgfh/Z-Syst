<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;

class BankCardGateway extends BasePaymentGateway
{
    public function getGatewayType(): string
    {
        return CompanyPaymentGateway::GATEWAY_BANK_CARD;
    }

    public function getGatewayName(): string
    {
        return 'Bank Card';
    }

    public function getRequiredConfigFields(): array
    {
        return [
            'merchant_id' => 'Merchant ID',
            'api_key' => 'API Key',
            'api_secret' => 'API Secret',
            'environment' => 'Environment (sandbox/production)',
            'accept_meeza' => 'Accept Meeza Payments',
            'accept_visamastercard' => 'Accept Visa/Mastercard',
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
            $cardData = $paymentData['card_data'] ?? [];

            if (empty($cardData)) {
                throw new \Exception('Card data is required for bank card payment');
            }

            // Bank Card API integration (would typically use Payment Provider like Paymob, Fawry Pay, etc.)
            $response = $this->callBankCardApi([
                'merchant_id' => $this->config['merchant_id'],
                'amount' => $amount,
                'card_number' => $cardData['card_number'] ?? null,
                'card_holder' => $cardData['card_holder'] ?? null,
                'expiry_month' => $cardData['expiry_month'] ?? null,
                'expiry_year' => $cardData['expiry_year'] ?? null,
                'cvv' => $cardData['cvv'] ?? null,
                'reference' => $transaction->internal_reference,
                'currency' => 'EGP',
                'customer_email' => $paymentData['customer_email'] ?? null,
                'customer_phone' => $paymentData['customer_phone'] ?? null,
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
            $response = $this->callBankCardStatusApi([
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

            $response = $this->callBankCardRefundApi([
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

    private function callBankCardApi(array $data): array
    {
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://api.paymentprovider.com.eg'
            : 'https://sandbox.paymentprovider.com.eg';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/v1/payments", $data);

        return $response->json() ?? [];
    }

    private function callBankCardStatusApi(array $data): array
    {
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://api.paymentprovider.com.eg'
            : 'https://sandbox.paymentprovider.com.eg';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->config['api_key'],
        ])->get("{$baseUrl}/v1/payments/status", $data);

        return $response->json() ?? [];
    }

    private function callBankCardRefundApi(array $data): array
    {
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://api.paymentprovider.com.eg'
            : 'https://sandbox.paymentprovider.com.eg';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/v1/refunds", $data);

        return $response->json() ?? [];
    }

    private function isSuccessfulResponse(array $response): bool
    {
        return isset($response['status']) && in_array($response['status'], ['success', 'completed', 'paid', 'captured']);
    }
}
