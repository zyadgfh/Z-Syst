<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;

class FawryGateway extends BasePaymentGateway
{
    public function getGatewayType(): string
    {
        return CompanyPaymentGateway::GATEWAY_FAWRY;
    }

    public function getGatewayName(): string
    {
        return 'Fawry';
    }

    public function getRequiredConfigFields(): array
    {
        return [
            'merchant_code' => 'Merchant Code',
            'security_key' => 'Security Key',
            'environment' => 'Environment (sandbox/production)',
        ];
    }

    public function validateConfig(array $config): bool
    {
        return ! empty($config['merchant_code']) &&
               ! empty($config['security_key']);
    }

    public function processPayment(array $paymentData): PaymentTransaction
    {
        $transaction = $this->createTransaction($paymentData);

        try {
            $amount = $this->calculateTotalAmount($paymentData['amount']);
            $customerPhone = $paymentData['customer_phone'] ?? null;
            $customerEmail = $paymentData['customer_email'] ?? null;

            if (empty($customerPhone)) {
                throw new \Exception('Customer phone number is required for Fawry payment');
            }

            // Fawry API integration
            $response = $this->callFawryApi([
                'merchant_code' => $this->config['merchant_code'],
                'merchant_ref_num' => $transaction->internal_reference,
                'amount' => $amount,
                'customer_mobile' => $customerPhone,
                'customer_email' => $customerEmail,
                'currency' => 'EGP',
                'description' => $paymentData['description'] ?? 'Payment',
            ]);

            if ($this->isSuccessfulResponse($response)) {
                $this->handlePaymentSuccess(
                    $transaction,
                    $response['reference_number'] ?? $response['merchant_ref_num'],
                    $response
                );
            } else {
                throw new \Exception($response['status_description'] ?? 'Payment failed');
            }

        } catch (\Exception $e) {
            $this->handlePaymentError($transaction, $e->getMessage());
        }

        return $transaction;
    }

    public function verifyPayment(string $referenceId): array
    {
        try {
            $response = $this->callFawryStatusApi([
                'merchant_code' => $this->config['merchant_code'],
                'merchant_ref_num' => $referenceId,
            ]);

            return [
                'success' => $this->isSuccessfulResponse($response),
                'status' => $response['payment_status'] ?? 'unknown',
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

            $response = $this->callFawryRefundApi([
                'merchant_code' => $this->config['merchant_code'],
                'reference_number' => $transaction->reference_id,
                'refund_amount' => $refundAmount,
            ]);

            if ($this->isSuccessfulResponse($response)) {
                $transaction->markAsRefunded($response);

                return [
                    'success' => true,
                    'refund_reference' => $response['refund_reference'] ?? null,
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['status_description'] ?? 'Refund failed',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function callFawryApi(array $data): array
    {
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://www.atfawry.com'
            : 'https://atfawry.com';

        $signature = $this->generateFawrySignature($data);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Signature' => $signature,
        ])->post("{$baseUrl}/api/payments", $data);

        return $response->json() ?? [];
    }

    private function callFawryStatusApi(array $data): array
    {
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://www.atfawry.com'
            : 'https://atfawry.com';

        $signature = $this->generateFawrySignature($data);

        $response = Http::withHeaders([
            'Signature' => $signature,
        ])->get("{$baseUrl}/api/payments/status", $data);

        return $response->json() ?? [];
    }

    private function callFawryRefundApi(array $data): array
    {
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://www.atfawry.com'
            : 'https://atfawry.com';

        $signature = $this->generateFawrySignature($data);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Signature' => $signature,
        ])->post("{$baseUrl}/api/refunds", $data);

        return $response->json() ?? [];
    }

    private function generateFawrySignature(array $data): string
    {
        $signatureString = $this->config['merchant_code'].
                          ($data['merchant_ref_num'] ?? '').
                          ($data['amount'] ?? '').
                          $this->config['security_key'];

        return hash('sha256', $signatureString);
    }

    private function isSuccessfulResponse(array $response): bool
    {
        return isset($response['status']) && in_array($response['status'], ['SUCCESS', 'PAID', 'COMPLETED']);
    }
}
