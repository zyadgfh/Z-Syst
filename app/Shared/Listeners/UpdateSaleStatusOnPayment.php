<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Services\SaleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Update Sale Status On Payment
 * 
 * يقوم بتحديث حالة المبيعات عند الدفع الناجح
 * وخصوصاً خصم المخزون وإنشاء الفاتورة
 */
class UpdateSaleStatusOnPayment implements ShouldQueue
{
    public function __construct(
        protected SaleService $saleService
    ) {}

    /**
     * Handle the event
     */
    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;

        // Only process sale payments
        if ($payment->reference_type !== 'sale') {
            return;
        }

        DB::transaction(function () use ($payment) {
            // 1. Get the sale
            $sale = $payment->reference;

            if (!$sale) {
                Log::warning('Payment succeeded but sale not found', [
                    'payment_id' => $payment->id,
                    'reference_id' => $payment->reference_id,
                ]);
                return;
            }

            // 2. Deduct stock
            foreach ($sale->items as $item) {
                $this->saleService->deductStock(
                    productId: $item->product_id,
                    branchId: $sale->branch_id,
                    quantity: $item->quantity,
                    reference: $sale
                );
            }

            // 3. Update sale status
            $sale->update([
                'payment_status' => 'paid',
                'payment_id' => $payment->id,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // 4. Generate invoice
            $this->saleService->generateInvoice($sale);

            // 5. Send receipt (SMS/Email/WhatsApp)
            // TODO: Implement notification service integration
        });
    }
}