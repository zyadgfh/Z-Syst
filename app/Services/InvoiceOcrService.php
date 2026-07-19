<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceOcrService
{
    /**
     * Process invoice image/PDF using OCR
     */
    public function processInvoice(UploadedFile $file, int $companyId): array
    {
        // Store the uploaded file temporarily
        $path = $file->store('invoices/ocr_temp', 'public');
        $fullPath = storage_path("app/public/{$path}");

        try {
            // Extract text using OCR
            $extractedText = $this->extractText($fullPath, $file->getClientMimeType());

            // Parse the extracted text to find product information
            $items = $this->parseInvoiceItems($extractedText);

            // Try to match products by name/code
            $matchedItems = $this->matchProducts($items, $companyId);

            return [
                'file_path' => $path,
                'raw_text' => $extractedText,
                'extracted_items' => $items,
                'matched_items' => $matchedItems,
                'confidence_score' => $this->calculateConfidence($matchedItems),
                'processed_at' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('OCR Processing failed: ' . $e->getMessage(), [
                'company_id' => $companyId,
                'file' => $path,
            ]);

            throw $e;
        }
    }

    /**
     * Extract text from image/PDF using OCR
     */
    protected function extractText(string $filePath, string $mimeType): string
    {
        // Check if Tesseract OCR is available
        if (class_exists('Thién\TesseractOCR\TesseractOCR')) {
            return $this->extractWithTesseract($filePath);
        }

        // Fallback to basic text extraction for PDFs
        if (strpos($mimeType, 'pdf') !== false) {
            return $this->extractPdfText($filePath);
        }

        // Fallback to simple response for image files
        return $this->extractImageText($filePath);
    }

    /**
     * Extract text using Tesseract OCR
     */
    protected function extractWithTesseract(string $filePath): string
    {
        try {
            $tesseract = new \Thién\TesseractOCR\TesseractOCR($filePath);
            $tesseract->lang('eng', 'ara'); // Support both English and Arabic
            return $tesseract->run();
        } catch (\Exception $e) {
            Log::warning('Tesseract OCR not available, using fallback');
            return "OCR Processing Required - Please install tesseract-ocr package";
        }
    }

    /**
     * Extract text from PDF (basic)
     */
    protected function extractPdfText(string $filePath): string
    {
        $content = file_get_contents($filePath);

        // Basic PDF text extraction - in production use smalot/pdfparser
        if (preg_match('/\((.*?)\)/s', $content, $matches)) {
            return $matches[1] ?? '';
        }

        return '';
    }

    /**
     * Extract text from image (basic)
     */
    protected function extractImageText(string $filePath): string
    {
        // For now, return placeholder - in production integrate with OCR API
        return "Invoice image uploaded - OCR processing required";
    }

    /**
     * Parse invoice items from extracted text
     */
    protected function parseInvoiceItems(string $text): array
    {
        $items = [];

        // Common patterns for invoice items:
        // - Product name / Medicine name
        // - Quantity
        // - Price / Unit cost
        // - Batch number
        // - Expiry date

        // Split text into lines
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            // Try to match common patterns
            // Pattern: Product name, quantity, price, batch, expiry
            if (preg_match('/([^\d,]+)[\s,]+(\d+)[\s,]+([\d.]+)[\s,]+([A-Z0-9-]+)[\s,]+(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                $items[] = [
                    'product_name' => trim($matches[1]),
                    'quantity' => (int) $matches[2],
                    'unit_price' => (float) $matches[3],
                    'batch_number' => $matches[4],
                    'expiry_date' => $matches[5],
                ];
            }
        }

        return $items;
    }

    /**
     * Match extracted items to products in database
     */
    protected function matchProducts(array $items, int $companyId): array
    {
        $matched = [];

        foreach ($items as $item) {
            // Try to match by product name or code
            $product = Product::where('company_id', $companyId)
                ->where(function ($q) use ($item) {
                    $q->where('productName', 'like', '%' . $item['product_name'] . '%')
                      ->orWhere('productCode', 'like', '%' . $item['product_name'] . '%')
                      ->orWhere('generic_name', 'like', '%' . $item['product_name'] . '%');
                })
                ->first();

            $matched[] = [
                'extracted_name' => $item['product_name'],
                'product_id' => $product?->id,
                'matched_product_name' => $product?->productName,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'batch_number' => $item['batch_number'],
                'expiry_date' => $item['expiry_date'],
                'match_confidence' => $product ? 'high' : 'low',
            ];
        }

        return $matched;
    }

    /**
     * Calculate confidence score for OCR results
     */
    protected function calculateConfidence(array $matchedItems): float
    {
        $total = count($matchedItems);
        if ($total === 0) {
            return 0;
        }

        $highConfidence = count(array_filter($matchedItems, fn ($item) => $item['match_confidence'] === 'high'));

        return round(($highConfidence / $total) * 100, 2);
    }

    /**
     * Update stock from OCR processed items
     */
    public function updateStockFromOcr(array $matchedItems, int $companyId, int $supplierId = null): array
    {
        $updated = [];
        $errors = [];

        foreach ($matchedItems as $item) {
            if (!$item['product_id']) {
                $errors[] = "No product matched for: {$item['extracted_name']}";
                continue;
            }

            try {
                $product = Product::findOrFail($item['product_id']);

                // Update or create stock
                $stock = \App\Models\Stock::where('product_id', $product->id)
                    ->where('batch_no', $item['batch_number'])
                    ->first();

                if ($stock) {
                    $stock->increment('productStock', $item['quantity']);
                } else {
                    \App\Models\Stock::create([
                        'company_id' => $companyId,
                        'product_id' => $product->id,
                        'productStock' => $item['quantity'],
                        'batch_no' => $item['batch_number'],
                        'expire_date' => $item['expiry_date'],
                    ]);
                }

                $updated[] = $product->productName;
            } catch (\Exception $e) {
                $errors[] = "Failed to update stock for product ID {$item['product_id']}";
            }
        }

        return [
            'updated_products' => $updated,
            'errors' => $errors,
            'success_count' => count($updated),
            'error_count' => count($errors),
        ];
    }
}
