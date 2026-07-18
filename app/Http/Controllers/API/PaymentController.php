<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Services\Payment\DTOs\PaymentRequestDTO;
use App\Services\Payment\DTOs\RefundRequestDTO;
use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Models\PaymentTransaction;
use App\Services\Payment\Services\PaymentGatewayFactory;
use App\Services\Payment\Services\PaymentProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Payment Controller
 * 
 * واجهة برمجة التطبيقات لعمليات الدفع
 */
class PaymentController extends BaseController
{
    public function __construct(
        protected PaymentGatewayFactory $gatewayFactory,
        protected PaymentProcessor $paymentProcessor
    ) {}

    /**
     * Get available payment methods for the company
     */
    public function availableMethods(Request $request): JsonResponse
    {
        $methods = $this->paymentProcessor->getAvailableMethods();

        return $this->sendResponse(
            $methods,
            'Payment methods retrieved successfully'
        );
    }

    /**
     * Initiate a new payment
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payable_type' => 'nullable|string',
            'payable_id' => 'nullable|integer',
            'payment_method_type' => 'required|string|in:vodafone_cash,orange_cash,etisalat_cash,instapay,we_pay,cash,bnpl',
            'amount' => 'required|numeric|min:1|max:50000',
            'currency' => 'sometimes|string|size:3',
            'merchant_reference' => 'sometimes|string|unique:payment_transactions,external_reference',
            'mobile_number' => 'sometimes|string|regex:/^01[0-9]{9}$/',
            'description' => 'sometimes|string|max:255',
            'metadata' => 'sometimes|array',
        ]);

        try {
            $dto = PaymentRequestDTO::fromArray($validated);
            
            $transaction = $this->paymentProcessor->processSinglePayment(
                PaymentMethodType::from($validated['payment_method_type']),
                $dto->toArray()
            );

            return $this->sendResponse(
                $transaction,
                'Payment initiated successfully'
            );

        } catch (PaymentException $e) {
            return $this->sendError(
                $e->getMessage(),
                [],
                $e->getCode() ?: 400
            );
        }
    }

    /**
     * Verify payment status
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|exists:payment_transactions,id',
        ]);

        try {
            $transaction = PaymentTransaction::findOrFail($validated['transaction_id']);
            
            $updated = $this->paymentProcessor->verifyTransaction($transaction);

            return $this->sendResponse(
                $updated,
                'Payment status verified'
            );

        } catch (PaymentException $e) {
            return $this->sendError(
                $e->getMessage(),
                [],
                $e->getCode() ?: 400
            );
        }
    }

    /**
     * Process refund
     */
    public function refund(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_uuid' => 'required|exists:payment_transactions,external_reference',
            'amount' => 'sometimes|numeric|min:0',
            'reason' => 'sometimes|string|max:255',
        ]);

        try {
            $transaction = PaymentTransaction::where('external_reference', $validated['payment_uuid'])->firstOrFail();
            
            $refunded = $this->paymentProcessor->refundTransaction(
                $transaction,
                $validated['amount'] ?? null,
                $validated['reason'] ?? null
            );

            return $this->sendResponse(
                $refunded,
                'Payment refunded successfully'
            );

        } catch (PaymentException $e) {
            return $this->sendError(
                $e->getMessage(),
                [],
                $e->getCode() ?: 400
            );
        }
    }

    /**
     * Get payment details
     */
    public function show(Request $request, PaymentTransaction $transaction): JsonResponse
    {
        return $this->sendResponse(
            $transaction,
            'Payment retrieved successfully'
        );
    }

    /**
     * List payments with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = PaymentTransaction::query()
            ->where('company_id', $request->user()->company_id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->filled('payment_method_type')) {
            $query->where('payment_method_type', $request->payment_method_type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->paginate($request->get('per_page', 15));

        return $this->sendResponse(
            $payments,
            'Payments retrieved successfully'
        );
    }
}