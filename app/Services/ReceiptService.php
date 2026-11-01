<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\ReceiptSetting;
use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptService
{
    public function getOrCreateSettings(int $businessId): ReceiptSetting
    {
        return ReceiptSetting::firstOrCreate(
            ['business_id' => $businessId],
            ['receipt_footer' => 'Thank you for your business!', 'show_barcode' => true, 'show_qr_code' => false, 'is_active' => true]
        );
    }

    public function generateForSale(Sale $sale, string $format = 'pdf'): Receipt
    {
        $receipt = $this->generateSaleReceipt($sale->id, $format);
        $number = $this->generateReceiptNumber('RCPT');
        $receipt->update([
            'receipt_number' => $number,
            'data' => array_merge($receipt->data ?? [], ['receipt_number' => $number,
                'paid_amount' => $sale->paidAmount,
                'due_amount' => $sale->dueAmount,
                'payment_type' => $sale->paymentType,
            ]),
        ]);
        return $receipt->fresh();
    }

    public function generateForPurchase(Purchase $purchase, string $format = 'pdf'): Receipt
    {
        $receipt = $this->generatePurchaseReceipt($purchase->id, $format);
        $number = $this->generateReceiptNumber('RCPT');
        $receipt->update([
            'receipt_number' => $number,
            'data' => array_merge($receipt->data ?? [], ['receipt_number' => $number,
                'paid_amount' => $purchase->paidAmount,
                'due_amount' => $purchase->dueAmount,
                'payment_type' => $purchase->paymentType,
            ]),
        ]);
        return $receipt->fresh();
    }

    public function getBySale(Sale $sale): ?Receipt
    {
        return Receipt::where('sale_id', $sale->id)->latest()->first();
    }

    public function renderPdf(Receipt $receipt): string
    {
        $data = $receipt->data;
        if ($receipt->type === 'sale') {
            $data['sale'] = Sale::with(['business', 'party'])->findOrFail($receipt->sale_id);
            $data['customer'] = $data['sale']->party;
        } elseif ($receipt->purchase_id) {
            $data['purchase'] = Purchase::with(['business', 'party'])->findOrFail($receipt->purchase_id);
            $data['supplier'] = $data['purchase']->party;
        }
        $view = $data['type'] === 'sale' ? 'receipts.sale-receipt' : 'receipts.purchase-receipt';
        return Pdf::loadView($view, ['data' => $data, 'receipt' => $receipt])->output();
    }

    /**
     * Generate receipt for sale
     */
    public function generateSaleReceipt(int $saleId, string $format = 'pdf'): Receipt
    {
        return DB::transaction(function () use ($saleId, $format) {
            $sale = Sale::with(['details.product', 'party'])->findOrFail($saleId);
            $setting = ReceiptSetting::forBusiness($sale->business_id)->active()->first();

            $receiptNumber = $this->generateReceiptNumber('SALE');
            
            $receiptData = [
                'type' => 'sale',
                'sale' => $sale,
                'items' => $sale->details->map(function ($detail) {
                    return [
                        'product_name' => $detail->product?->name ?? 'Unknown',
                        'quantity' => $detail->quantities,
                        'price' => $detail->price ?? $detail->purchase_without_tax,
                        'total' => ($detail->price ?? $detail->purchase_without_tax ?? 0) * ($detail->quantities ?? 0),
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
            $purchase = Purchase::with(['details.product', 'party'])->findOrFail($purchaseId);
            $setting = ReceiptSetting::forBusiness($purchase->business_id)->active()->first();

            $receiptNumber = $this->generateReceiptNumber('PURCHASE');
            
            $receiptData = [
                'type' => 'purchase',
                'purchase' => $purchase,
                'items' => $purchase->details->map(function ($detail) {
                    return [
                        'product_name' => $detail->product?->name ?? 'Unknown',
                        'quantity' => $detail->quantities,
                        'price' => $detail->price ?? $detail->purchase_without_tax,
                        'total' => ($detail->price ?? $detail->purchase_without_tax ?? 0) * ($detail->quantities ?? 0),
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
     * Generate unique receipt number
     */
    protected function generateReceiptNumber(string $prefix): string
    {
        do {
            $number = $prefix . '-' . date('Ymd') . '-' . strtoupper(Str::random(6));
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
