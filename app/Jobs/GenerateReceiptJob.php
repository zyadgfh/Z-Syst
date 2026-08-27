<?php

namespace App\Jobs;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $maxExceptions = 2;

    public function __construct(
        public int $saleId,
        public string $format = 'pdf',
    ) {
        $this->onQueue('receipts');
    }

    public function handle(): ?string
    {
        $sale = Sale::with(['details.product', 'party', 'tax', 'user'])
            ->find($this->saleId);

        if (! $sale) {
            Log::warning("GenerateReceiptJob: Sale not found: {$this->saleId}");
            return null;
        }

        Log::info("Generating receipt for sale: {$this->saleId}", [
            'invoice_number' => $sale->invoiceNumber,
            'format' => $this->format,
        ]);

        // Generate receipt content
        $receiptPath = $this->generateReceiptFile($sale);

        Log::info("Receipt generated", [
            'sale_id' => $this->saleId,
            'path' => $receiptPath,
        ]);

        return $receiptPath;
    }

    private function generateReceiptFile(Sale $sale): string
    {
        $directory = storage_path('app/receipts');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = "receipt_{$sale->invoiceNumber}_" . now()->format('Ymd_His') . ".{$this->format}";
        $filepath = "{$directory}/{$filename}";

        // Build receipt data
        $receiptData = [
            'business' => $sale->business ?? null,
            'invoice_number' => $sale->invoiceNumber,
            'date' => $sale->saleDate ?? $sale->created_at->format('Y-m-d'),
            'customer' => $sale->party?->partyName ?? 'Walk-in',
            'items' => $sale->details->map(fn ($d) => [
                'product' => $d->product->productName ?? 'N/A',
                'qty' => $d->quantities,
                'price' => $d->price,
                'total' => $d->quantities * $d->price,
            ]),
            'subtotal' => $sale->totalAmount,
            'tax' => $sale->tax_amount ?? 0,
            'discount' => $sale->discountAmount ?? 0,
            'total' => $sale->totalAmount,
            'paid' => $sale->paidAmount,
            'due' => $sale->dueAmount,
            'payment_type' => $sale->paymentType,
            'cashier' => $sale->user?->name ?? 'System',
        ];

        // Write receipt as JSON (can be extended to PDF generation)
        file_put_contents($filepath, json_encode($receiptData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $filepath;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateReceiptJob failed permanently", [
            'sale_id' => $this->saleId,
            'error' => $exception->getMessage(),
        ]);
    }
}
