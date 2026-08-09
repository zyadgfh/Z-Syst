<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentException;
use App\Models\CompanyPaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PosPaymentController extends Controller
{
    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Get available payment gateways for POS.
     */
    public function getAvailableGateways(Request $request)
    {
        try {
            $companyId = Auth::user()->business_id ?? $request->input('company_id');
            $branchId = $request->input('branch_id');

            if (! $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company ID is required',
                ], 400);
            }

            $gateways = $this->paymentGatewayService->getAvailableGateways($companyId, $branchId);

            return response()->json([
                'success' => true,
                'gateways' => $gateways->map(function ($gateway) {
                    return [
                        'id' => $gateway->id,
                        'type' => $gateway->gateway_type,
                        'name' => $gateway->gateway_type_label,
                        'is_active' => $gateway->is_active,
                        'transaction_fee' => $gateway->transaction_fee,
                        'transaction_fee_type' => $gateway->transaction_fee_type,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get available gateways', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment gateways',
            ], 500);
        }
    }

    /**
     * Process POS payment.
     */
    public function processPayment(Request $request)
    {
        try {
            $request->validate([
                'gateway_id' => 'required|exists:company_payment_gateways,id',
                'amount' => 'required|numeric|min:0',
                'invoice_id' => 'nullable|exists:invoices,id',
                'customer_phone' => 'nullable|string|max:20',
                'customer_email' => 'nullable|email',
            ]);

            $gateway = CompanyPaymentGateway::findOrFail($request->gateway_id);

            if (! $gateway->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment gateway is not active',
                ], 400);
            }

            $paymentData = $this->preparePaymentData($request, $gateway);
            $transaction = $this->paymentGatewayService->processPayment($gateway->id, $paymentData);

            return response()->json([
                'success' => $transaction->status === PaymentTransaction::STATUS_COMPLETED,
                'transaction_id' => $transaction->id,
                'reference_id' => $transaction->reference_id,
                'internal_reference' => $transaction->internal_reference,
                'status' => $transaction->status,
                'status_label' => $transaction->status_label,
                'amount' => $transaction->amount,
                'message' => $this->getPaymentMessage($transaction),
            ]);

        } catch (PaymentException $e) {
            Log::error('Payment processing error', [
                'error' => $e->getMessage(),
                'gateway_id' => $request->gateway_id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Unexpected payment processing error', [
                'error' => $e->getMessage(),
                'gateway_id' => $request->gateway_id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
            ], 500);
        }
    }

    /**
     * Prepare payment data from request.
     */
    private function preparePaymentData(Request $request, CompanyPaymentGateway $gateway): array
    {
        $paymentData = [
            'amount' => $request->amount,
            'currency' => 'EGP',
            'customer_phone' => $request->customer_phone,
            'customer_email' => $request->customer_email,
            'transaction_type' => PaymentTransaction::TYPE_SALE,
            'metadata' => [
                'invoice_id' => $request->invoice_id,
                'pos_session_id' => $request->pos_session_id,
                'branch_id' => $gateway->branch_id,
                'company_id' => $gateway->company_id,
            ],
            'processed_by' => Auth::id(),
        ];

        if ($gateway->gateway_type === CompanyPaymentGateway::GATEWAY_BANK_CARD) {
            $paymentData['card_data'] = [
                'card_number' => $request->card_number,
                'card_holder' => $request->card_holder,
                'expiry_month' => $request->expiry_month,
                'expiry_year' => $request->expiry_year,
                'cvv' => $request->cvv,
            ];
        }

        if ($gateway->gateway_type === CompanyPaymentGateway::GATEWAY_CASH) {
            $paymentData['received_amount'] = $request->received_amount;
            $paymentData['payment_notes'] = $request->payment_notes;
            $paymentData['verified_by'] = $request->verified_by;
        }

        return $paymentData;
    }

    /**
     * Get appropriate payment message based on transaction status.
     */
    private function getPaymentMessage(PaymentTransaction $transaction): string
    {
        return match ($transaction->status) {
            PaymentTransaction::STATUS_COMPLETED => 'Payment processed successfully',
            PaymentTransaction::STATUS_PENDING => 'Payment processing: '.$transaction->status,
            default => 'Payment failed',
        };
    }

    /**
     * Verify POS payment status.
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:payment_transactions,id',
        ]);

        try {
            $transaction = PaymentTransaction::findOrFail($request->transaction_id);

            if ($transaction->status === PaymentTransaction::STATUS_PENDING) {
                $verification = $this->paymentGatewayService->verifyPayment(
                    $transaction->gateway_id,
                    $transaction->reference_id
                );

                if ($verification['success']) {
                    $transaction->markAsCompleted($transaction->reference_id, $verification['data']);
                } else {
                    $transaction->markAsFailed($verification['error'] ?? 'Payment verification failed');
                }
            }

            return response()->json([
                'success' => $transaction->status === PaymentTransaction::STATUS_COMPLETED,
                'transaction' => [
                    'id' => $transaction->id,
                    'status' => $transaction->status,
                    'status_label' => $transaction->status_label,
                    'amount' => $transaction->amount,
                    'reference_id' => $transaction->reference_id,
                    'internal_reference' => $transaction->internal_reference,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process POS refund.
     */
    public function processRefund(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:payment_transactions,id',
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $transaction = PaymentTransaction::findOrFail($request->transaction_id);

            if ($transaction->status !== PaymentTransaction::STATUS_COMPLETED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only completed transactions can be refunded',
                ], 400);
            }

            $refundAmount = $request->amount ?? $transaction->amount;
            $result = $this->paymentGatewayService->processRefund($transaction->id, $refundAmount);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'refund_id' => $result['refund_id'] ?? null,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Refund failed',
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate change for cash payment.
     */
    public function calculateChange(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'received_amount' => 'required|numeric|min:0',
        ]);

        $change = max(0, $request->received_amount - $request->amount);

        return response()->json([
            'success' => true,
            'change' => $change,
            'is_sufficient' => $request->received_amount >= $request->amount,
        ]);
    }

    /**
     * Get payment statistics for POS dashboard.
     */
    public function getPaymentStats(Request $request)
    {
        $companyId = Auth::user()->business_id ?? $request->input('company_id');
        $branchId = $request->input('branch_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        try {
            $stats = $this->paymentGatewayService->getTransactionStats($companyId, $branchId, [
                'transaction_type' => PaymentTransaction::TYPE_SALE,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]);

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
