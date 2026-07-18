<?php

namespace App\Services\Invoice;

use App\Models\Sale;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

class InvoicePDFGenerator
{
    /**
     * Generate PDF invoice for download
     *
     * @param Sale $sale The sale model
     * @return string Path to generated PDF
     */
    public function generate(Sale $sale): string
    {
        $invoiceNumber = $sale->invoiceNumber;
        $filename = "invoices/invoice-{$invoiceNumber}.pdf";
        $path = "public/{$filename}";

        // Ensure directory exists
        if (!Storage::exists('public/invoices')) {
            Storage::makeDirectory('public/invoices');
        }

        // Get sale details
        $details = $sale->details ?? collect([]);
        $items = $details->map(function ($detail) {
            return [
                'name' => $detail->product->name ?? 'منتج',
                'quantity' => $detail->quantity ?? 1,
                'unit_price' => $detail->unit_price ?? 0,
                'line_total' => $detail->line_total ?? ($detail->quantity * $detail->unit_price),
            ];
        })->toArray();

        $html = View::make('invoices.sale-print', [
            'sale' => $sale,
            'invoice_number' => $invoiceNumber,
            'date' => $sale->created_at->format('Y-m-d'),
            'customer' => $sale->party->name ?? 'غير محدد',
            'customer_phone' => $sale->party->phone ?? null,
            'items' => $items,
            'subtotal' => $sale->totalAmount / 1.14 ?? $sale->totalAmount, // Approximate
            'tax_amount' => $sale->totalAmount * 0.14 ?? 0,
            'total_amount' => $sale->totalAmount,
            'payment_method' => $sale->paymentType ?? 'نقداً',
            'notes' => $sale->meta['notes'] ?? null,
            'company' => $sale->company,
            'branch' => $sale->branch,
        ])->render();

        // Try to use DOMPDF if available
        if (class_exists(\Barryvdh\DomPDF\Facade::class)) {
            $pdf = \Barryvdh\DomPDF\Facade::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            Storage::put($path, $pdf->output());
            return $filename;
        }

        // Fallback: Save HTML as temporary file
        $tempPath = storage_path("app/{$path}");
        file_put_contents($tempPath, $html);
        return $filename;
    }

    /**
     * Generate PDF for thermal printer (80mm paper)
     */
    public function generateForThermal(Sale $sale): string
    {
        $invoiceNumber = $sale->invoiceNumber;
        $filename = "invoices/thermal-{$invoiceNumber}.pdf";
        $path = "public/{$filename}";

        if (!Storage::exists('public/invoices')) {
            Storage::makeDirectory('public/invoices');
        }

        $details = $sale->details ?? collect([]);
        $items = $details->map(function ($detail) {
            return [
                'name' => $detail->product->name ?? 'منتج',
                'quantity' => $detail->quantity ?? 1,
                'unit_price' => $detail->unit_price ?? 0,
                'line_total' => $detail->line_total ?? ($detail->quantity * $detail->unit_price),
            ];
        })->toArray();

        $html = View::make('invoices.sale-print-thermal', [
            'sale' => $sale,
            'invoice_number' => $invoiceNumber,
            'items' => $items,
            'total_amount' => $sale->totalAmount,
        ])->render();

        if (class_exists(\Barryvdh\DomPDF\Facade::class)) {
            $pdf = \Barryvdh\DomPDF\Facade::loadHTML($html);
            $pdf->setPaper([0, 0, 226.77, 700], 'portrait'); // 80mm width
            Storage::put($path, $pdf->output());
            return $filename;
        }

        return str_replace('.pdf', '.html', $path);
    }

    /**
     * Generate PDF for print with custom options
     */
    public function generateForPrint(Sale $sale, array $options = []): string
    {
        $template = $options['template'] ?? 'a4';

        return match ($template) {
            'thermal' => $this->generateForThermal($sale),
            'a4' => $this->generate($sale),
            default => $this->generate($sale),
        };
    }

    /**
     * Get PDF URL
     */
    public function getPdfUrl(Sale $sale, ?string $path = null): string
    {
        $pdfPath = $path ?? $this->generate($sale);
        return asset('storage/' . $pdfPath);
    }
}