<?php

namespace App\Services;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

class InvoiceImageService
{
    /**
     * Generate invoice HTML content for display/print/save
     */
    public function generateInvoiceHtml($sale): string
    {
        $invoiceNumber = 'S-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT);
        
        $data = [
            'sale' => $sale,
            'invoice_number' => $invoiceNumber,
            'date' => $sale->created_at->format('Y-m-d'),
            'customer' => $sale->customer_name,
            'customer_phone' => $sale->customer_phone ?? null,
            'items' => $sale->saleItems ?? $sale->items,
            'subtotal' => $sale->subtotal,
            'tax_amount' => $sale->tax_amount,
            'total_amount' => $sale->total_amount,
            'payment_method' => $sale->payment_method,
            'notes' => $sale->notes,
            'company' => $sale->company ?? null,
        ];
        
        return View::make('invoices.sale-print', $data)->render();
    }

    /**
     * Generate invoice image (base64) from sale data
     * Using html2canvas frontend approach - generates a simple image for demo
     */
    public function generateInvoiceImage($sale): string
    {
        // For production: Use barryvdh/laravel-dompdf to generate PDF
        // Then convert PDF to image using imagemagick
        
        // Generate a simple invoice image using GD
        $invoiceNumber = 'S-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT);
        
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
        imagestring($image, 3, 30, $y + 20, "العميل: " . ($sale->customer_name ?? 'غير محدد'), $dark);
        imagestring($image, 3, 30, $y + 40, "طريقة الدفع: " . ($sale->payment_method ?? 'نقداً'), $dark);
        
        // Items table header
        $y = 140;
        imagefilledrectangle($image, 30, $y, 570, $y + 30, $gray);
        imagestring($image, 3, 40, $y + 8, "الصنف", $dark);
        imagestring($image, 3, 350, $y + 8, "الكمية", $dark);
        imagestring($image, 3, 450, $y + 8, "السعر", $dark);
        imagestring($image, 3, 520, $y + 8, "الإجمالي", $dark);
        
        // Items
        $items = $sale->saleItems ?? $sale->items;
        $itemY = $y + 40;
        if (is_array($items)) {
            foreach ($items as $index => $item) {
                if ($itemY > 650) break; // Prevent overflow
                $itemName = is_object($item) ? $item->name : ($item['name'] ?? 'منتج');
                $itemQty = is_object($item) ? $item->quantity : ($item['quantity'] ?? 1);
                $itemPrice = is_object($item) ? $item->unit_price : ($item['price'] ?? 0);
                $itemTotal = is_object($item) ? $item->line_total : ($item['line_total'] ?? 0);
                
                imagestring($image, 3, 40, $itemY, substr($itemName, 0, 30), $dark);
                imagestring($image, 3, 350, $itemY, $itemQty, $dark);
                imagestring($image, 3, 450, $itemY, $itemPrice . " ج.م", $dark);
                imagestring($image, 3, 520, $itemY, $itemTotal . " ج.م", $dark);
                $itemY += 25;
            }
        }
        
        // Total
        $y = 700;
        imagestring($image, 4, 400, $y, "الإجمالي الكلي:", $blue);
        imagestring($image, 4, 500, $y, number_format($sale->total_amount ?? 0, 2) . " ج.م", $blue);
        
        // Save image
        $imagePath = storage_path("app/invoices/invoice_{$sale->id}.jpg");
        if (!file_exists(storage_path('app/invoices'))) {
            mkdir(storage_path('app/invoices'), 0755, true);
        }
        
        ob_start();
        imagejpeg($image, null, 90);
        $imageData = ob_get_clean();
        
        imagedestroy($image);
        
        // Save to file
        imagejpeg(imagecreatefromstring($imageData), $imagePath);
        
        return base64_encode($imageData);
    }

    /**
     * Generate PDF invoice for download/print
     */
    public function generateInvoicePdf($sale): string
    {
        if (!file_exists(storage_path('app/invoices'))) {
            mkdir(storage_path('app/invoices'), 0755, true);
        }
        
        $invoiceNumber = 'S-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT);
        $pdfPath = storage_path("app/invoices/invoice_{$sale->id}.pdf");
        
        $html = $this->generateInvoiceHtml($sale);
        
        // For production: use barryvdh/laravel-dompdf
        file_put_contents($pdfPath, $html); // Placeholder - stores HTML temporarily
        
        return $pdfPath;
    }
}
