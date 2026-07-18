<?php

namespace App\Services\Invoice;

use App\Models\Sale;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

class InvoiceImageGenerator
{
    /**
     * Generate invoice image from sale model
     *
     * @param Sale $sale The sale model
     * @return string Path to generated image
     */
    public function generate(Sale $sale): string
    {
        $invoiceNumber = $sale->invoiceNumber;
        $filename = "invoices/invoice-{$invoiceNumber}.png";
        $path = "public/{$filename}";

        // Ensure directory exists
        if (!Storage::exists('public/invoices')) {
            Storage::makeDirectory('public/invoices');
        }

        // Get invoice details
        $details = $sale->details ?? collect([]);
        $items = $details->map(function ($detail) {
            return [
                'name' => $detail->product->name ?? 'منتج',
                'quantity' => $detail->quantity ?? 1,
                'unit_price' => $detail->unit_price ?? 0,
                'line_total' => $detail->line_total ?? ($detail->quantity * $detail->unit_price),
            ];
        })->toArray();

        // Generate HTML first
        $html = View::make('invoices.sale-print', [
            'sale' => $sale,
            'invoice_number' => $invoiceNumber,
            'date' => $sale->created_at->format('Y-m-d'),
            'customer' => $sale->party->name ?? 'عميلنا العزيز',
            'customer_phone' => $sale->party->phone ?? null,
            'items' => $items,
            'subtotal' => $sale->totalAmount / 1.14 ?? $sale->totalAmount,
            'tax_amount' => $sale->totalAmount * 0.14 ?? 0,
            'total_amount' => $sale->totalAmount,
            'payment_method' => $sale->paymentType ?? 'نقداً',
            'company' => $sale->company,
            'branch' => $sale->branch,
        ])->render();

        // Try browsershot if available (puppeteer)
        if (class_exists(\Spatie\Browsershot\Browsershot::class)) {
            $fullPath = storage_path("app/{$path}");
            \Spatie\Browsershot\Browsershot::html($html)
                ->windowSize(800, 1200)
                ->deviceScaleFactor(2)
                ->save($fullPath);
            return $filename;
        }

        // Fallback: Generate using GD
        return $this->generateWithGD($sale, $invoiceNumber);
    }

    /**
     * Generate invoice image using GD library (fallback)
     */
    protected function generateWithGD(Sale $sale, string $invoiceNumber): string
    {
        $filename = "invoices/invoice-{$invoiceNumber}.jpg";
        $path = "public/{$filename}";

        // Create invoice image
        $width = 600;
        $height = 800;
        $image = imagecreate($width, $height);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $blue = imagecolorallocate($image, 102, 118, 234);
        $dark = imagecolorallocate($image, 45, 55, 72);
        $gray = imagecolorallocate($image, 160, 174, 192);

        // Fill background
        imagefilledrectangle($image, 0, 0, $width, $height, $white);

        // Header
        imagefilledrectangle($image, 0, 0, $width, 60, $blue);
        imagestring($image, 5, 200, 20, "فاتورة مبيعات", $white);
        imagestring($image, 4, 200, 40, "#" . $invoiceNumber, $white);

        // Invoice details
        $y = 80;
        imagestring($image, 3, 30, $y, "التاريخ: " . $sale->created_at->format('Y-m-d'), $dark);
        imagestring($image, 3, 30, $y + 20, "العميل: " . ($sale->party->name ?? 'غير محدد'), $dark);
        imagestring($image, 3, 30, $y + 40, "طريقة الدفع: " . ($sale->paymentType ?? 'نقداً'), $dark);

        // Items table header
        $y = 140;
        imagefilledrectangle($image, 30, $y, 570, $y + 30, $gray);
        imagestring($image, 3, 40, $y + 8, "الصنف", $dark);
        imagestring($image, 3, 250, $y + 8, "الكمية", $dark);
        imagestring($image, 3, 350, $y + 8, "السعر", $dark);
        imagestring($image, 3, 450, $y + 8, "الإجمالي", $dark);

        // Items
        $details = $sale->details ?? collect([]);
        $itemY = $y + 40;
        foreach ($details as $index => $detail) {
            if ($itemY > 650) break;
            $itemName = $detail->product->name ?? 'منتج';
            $itemQty = $detail->quantity ?? 1;
            $itemPrice = $detail->unit_price ?? 0;
            $itemTotal = $detail->line_total ?? ($itemQty * $itemPrice);

            imagestring($image, 3, 40, $itemY, substr($itemName, 0, 25), $dark);
            imagestring($image, 3, 250, $itemY, $itemQty, $dark);
            imagestring($image, 3, 350, $itemY, number_format($itemPrice, 2), $dark);
            imagestring($image, 3, 450, $itemY, number_format($itemTotal, 2), $dark);
            $itemY += 25;
        }

        // Total
        $y = 700;
        imagestring($image, 4, 350, $y, "الإجمالي الكلي:", $blue);
        imagestring($image, 4, 450, $y, number_format($sale->totalAmount ?? 0, 2) . " ج.م", $blue);

        // Save image
        $fullPath = storage_path("app/{$path}");
        imagejpeg($image, $fullPath, 90);
        imagedestroy($image);

        return $filename;
    }

    /**
     * Get image URL
     */
    public function getImageUrl(Sale $sale, ?string $path = null): string
    {
        $imagePath = $path ?? $this->generate($sale);
        return asset('storage/' . $imagePath);
    }
}