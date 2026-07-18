<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Services\Payment\DTOs\PaymentRequestDTO;
use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Services\PaymentProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POS Payment Controller
 * 
 * مدفوعات نقطة البيع (POS)
 */
class PosPaymentController extends BaseController
{
    public function __construct(
        protected PaymentProcessor $paymentProcessor
    ) {}

    /**
     * Quick pay for POS - simplified payment flow
     */
    public function quickPay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'payment_method_type' => 'required|string|in:vodafone_cash,orange_cash,etisalat_cash,instapay,we_pay,cash',
            'mobile_number' => 'sometimes|string|regex:/^01[0-9]{9}$/',
            'amount' => 'sometimes|numeric',
        ]);

        try {
            $sale = \App\Models\Sale::findOrFail($validated['sale_id']);
            
            $amount = $validated['amount'] ?? $sale->totalAmount;
            
            // Validate mobile number prefix for the selected wallet
            if (isset($validated['mobile_number'])) {
                $this->validateMobilePrefix(
                    $validated['mobile_number'],
                    $validated['payment_method_type']
                );
            }

            $transaction = $this->paymentProcessor->processSinglePayment(
                PaymentMethodType::from($validated['payment_method_type']),
                [
                    'amount' => $amount,
                    'reference_id' => $sale->id,
                    'reference_type' => \App\Models\Sale::class,
                    'mobile_number' => $validated['mobile_number'] ?? null,
                    'user_id' => $request->user()->id,
                ]
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
     * Split payment - multiple payment methods for one sale
     * 
     * مثال: 300 جنيه كاش + 200 جنيه فودافون كاش
     */
    public function splitPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'payments' => 'required|array|min:2',
            'payments.*.payment_method_type' => 'required|string|in:vodafone_cash,orange_cash,etisalat_cash,instapay,we_pay,cash,bnpl',
            'payments.*.amount' => 'required|numeric|min:1',
            'payments.*.mobile_number' => 'sometimes|string|regex:/^01[0-9]{9}$/',
        ]);

        try {
            $sale = \App\Models\Sale::findOrFail($validated['sale_id']);
            
            $result = $this->paymentProcessor->processMixedPayment(
                $validated['payments'],
                [
                    'reference_id' => $sale->id,
                    'reference_type' => \App\Models\Sale::class,
                    'user_id' => $request->user()->id,
                ]
            );

            return $this->sendResponse(
                $result,
                $result['is_complete'] 
                    ? 'Split payment completed successfully' 
                    : 'Split payment processed with warnings'
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
     * Validate Egyptian mobile number prefix for specific wallet
     */
    protected function validateMobilePrefix(string $phone, string $methodType): void
    {
        $prefixes = [
            'vodafone_cash' => ['0100', '0101', '0102', '0106', '0109'],
            'orange_cash' => ['0111', '0112', '0114', '0110', '0115'],
            'etisalat_cash' => ['0120', '0121', '0122', '0123', '0128'],
            'we_pay' => ['0150', '0151', '0155', '0156'],
            'instapay' => [], // InstaPay uses QR code, no phone validation
        ];

        $validPrefixes = $prefixes[$methodType] ?? [];
        
        if (empty($validPrefixes)) {
            return;
        }

        $phonePrefix = substr($phone, 0, 4);
        $isValid = in_array($phonePrefix, $validPrefixes);

        if (!$isValid) {
            throw new PaymentException(
                "Phone number prefix does not match {$methodType}",
                422
            );
        }
    }
}