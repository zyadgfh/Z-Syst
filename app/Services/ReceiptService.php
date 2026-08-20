<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Receipt;
use App\Models\ReceiptSetting;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReceiptService
{
    /**
     * Generate receipt for sale
     */
    public function generateSaleReceipt(int $saleId, string $format = 'pdf'): Receipt
    {
        return DB::transaction(function () use ($saleId, $format) {
            $sale = Sale::with(['details.product', 'party', 'business'])->findOrFail($saleId);
            $setting = ReceiptSetting::forBusiness($sale->business_id)->active()->first();

            $receiptNumber = $this->generateReceiptNumber('SALE');

            $receiptData = [
                'type' => 'sale',
                'sale' => $sale,
                'items' => $sale->details->map(function ($detail) {
                    return [
                        'product_name' => $detail->product?->name ?? 'Unknown',
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'total' => $detail->total,
                        'discount' => $detail->discount ?? 0,
                    ];
                }),
                'customer' => $sale->party,
                'subtotal' => $sale->totalAmount,
                'tax' => $sale->tax ?? 0,
                'discount' => $sale->discount ?? 0,
                'total' => $sale->netTotal ?? $sale->totalAmount,
                'paid' => $sale->paidAmount,
                'due' => $sale->dueAmount,
                'payment_method' => $sale->paymentType,
                'header' => $setting?->receipt_header,
                'footer' => $setting?->receipt_footer,
                'show_barcode' => $setting?->show_barcode ?? true,
                'show_qr_code' => $setting?->show_qr_code ?? false,
            ];

            $receipt = Receipt::create([
                'business_id' => $sale->business_id,
                'sale_id' => $saleId,
                'receipt_number' => $receiptNumber,
                'type' => 'sale',
                'format' => $format,
                'status' => 'generated',
                'user_id' => auth()->id(),
                'data' => $receiptData,
            ]);

            return $receipt;
        });
    }

    /**
     * Generate receipt for purchase
     */
    public function generatePurchaseReceipt(int $purchaseId, string $format = 'pdf'): Receipt
    {
        return DB::transaction(function () use ($purchaseId, $format) {
            $purchase = Purchase::with(['purchaseDetails.product', 'party', 'business'])->findOrFail($purchaseId);
            $setting = ReceiptSetting::forBusiness($purchase->business_id)->active()->first();

            $receiptNumber = $this->generateReceiptNumber('PURCHASE');

            $receiptData = [
                'type' => 'purchase',
                'purchase' => $purchase,
                'items' => $purchase->purchaseDetails->map(function ($detail) {
                    return [
                        'product_name' => $detail->product?->name ?? 'Unknown',
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'total' => $detail->total,
                        'discount' => $detail->discount ?? 0,
                    ];
                }),
                'supplier' => $purchase->party,
                'subtotal' => $purchase->totalAmount,
                'tax' => $purchase->tax ?? 0,
                'discount' => $purchase->discount ?? 0,
                'total' => $purchase->netTotal ?? $purchase->totalAmount,
                'paid' => $purchase->paidAmount,
                'due' => $purchase->dueAmount,
                'payment_method' => $purchase->paymentType,
                'header' => $setting?->receipt_header,
                'footer' => $setting?->receipt_footer,
                'show_barcode' => $setting?->show_barcode ?? true,
                'show_qr_code' => $setting?->show_qr_code ?? false,
            ];

            $receipt = Receipt::create([
                'business_id' => $purchase->business_id,
                'purchase_id' => $purchaseId,
                'receipt_number' => $receiptNumber,
                'type' => 'purchase',
                'format' => $format,
                'status' => 'generated',
                'user_id' => auth()->id(),
                'data' => $receiptData,
            ]);

            return $receipt;
        });
    }

    /**
     * Generate PDF receipt
     */
    public function generatePdf(Receipt $receipt)
    {
        $data = $receipt->data;
        $view = $data['type'] === 'sale' ? 'receipts.sale-receipt' : 'receipts.purchase-receipt';

        $pdf = Pdf::loadView($view, ['data' => $data, 'receipt' => $receipt]);

        return $pdf->download("receipt-{$receipt->receipt_number}.pdf");
    }

    /**
     * Generate HTML receipt
     */
    public function generateHtml(Receipt $receipt): string
    {
        $data = $receipt->data;
        $view = $data['type'] === 'sale' ? 'receipts.sale-receipt' : 'receipts.purchase-receipt';

        return view($view, ['data' => $data, 'receipt' => $receipt])->render();
    }

    /**
     * Mark receipt as printed
     */
    public function markAsPrinted(Receipt $receipt): Receipt
    {
        $receipt->update(['status' => 'printed']);

        return $receipt->fresh();
    }

    /**
     * Mark receipt as emailed
     */
    public function markAsEmailed(Receipt $receipt): Receipt
    {
        $receipt->update(['status' => 'emailed']);

        return $receipt->fresh();
    }

    /**
     * Update receipt settings
     */
    public function updateSettings(int $businessId, array $data): ReceiptSetting
    {
        $setting = ReceiptSetting::forBusiness($businessId)->first();

        if ($setting) {
            $setting->update($data);

            return $setting->fresh();
        }

        return ReceiptSetting::create(array_merge($data, ['business_id' => $businessId]));
    }

    /**
     * Get receipt settings
     */
    public function getSettings(int $businessId): ?ReceiptSetting
    {
        return ReceiptSetting::forBusiness($businessId)->active()->first();
    }

    /**
     * Get receipt statistics
     */
    public function getStatistics(int $businessId, array $filters = []): array
    {
        $query = Receipt::forBusiness($businessId);

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $receipts = $query->get();

        return [
            'total_receipts' => $receipts->count(),
            'sale_receipts' => $receipts->where('type', 'sale')->count(),
            'purchase_receipts' => $receipts->where('type', 'purchase')->count(),
            'printed_receipts' => $receipts->where('status', 'printed')->count(),
            'generated_receipts' => $receipts->where('status', 'generated')->count(),
            'receipts_by_format' => [
                'pdf' => $receipts->where('format', 'pdf')->count(),
                'html' => $receipts->where('format', 'html')->count(),
                'thermal' => $receipts->where('format', 'thermal')->count(),
            ],
        ];
    }

    /**
     * Get or create receipt settings for a business.
     */
    public function getOrCreateSettings(int $businessId): ReceiptSetting
    {
        $setting = ReceiptSetting::forBusiness($businessId)->first();

        if ($setting) {
            return $setting;
        }

        return ReceiptSetting::create([
            'business_id' => $businessId,
            'receipt_header' => 'Receipt',
            'receipt_footer' => 'Thank you for your purchase!',
            'show_barcode' => true,
            'show_qr_code' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Generate receipt for a sale model instance.
     */
    public function generateForSale(Sale $sale, string $format = 'pdf'): Receipt
    {
        return $this->generateSaleReceipt($sale->id, $format);
    }

    /**
     * Generate unique receipt number
     */
    protected function generateReceiptNumber(string $prefix): string
    {
        do {
            $number = $prefix.'-'.date('Ymd').'-'.strtoupper(Str::random(6));
        } while (Receipt::where('receipt_number', $number)->exists());

        return $number;
    }

    /**
     * Regenerate receipt (update data)
     */
    public function regenerateReceipt(Receipt $receipt): Receipt
    {
        if ($receipt->sale_id) {
            return $this->generateSaleReceipt($receipt->sale_id, $receipt->format);
        } elseif ($receipt->purchase_id) {
            return $this->generatePurchaseReceipt($receipt->purchase_id, $receipt->format);
        }

        throw new \Exception('Cannot regenerate receipt: no reference found');
    }
}
