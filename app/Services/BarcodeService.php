<?php

namespace App\Services;

use App\Models\Barcode;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use PDF;

class BarcodeService
{
    /**
     * Generate barcode for a product.
     */
    public function generateForProduct(Product $product, array $options = []): Barcode
    {
        $barcodeType = $options['type'] ?? Barcode::TYPE_CODE128;
        $size = $options['size'] ?? Barcode::SIZE_STANDARD;
        $businessId = $options['business_id'] ?? $product->business_id;
        $branchId = $options['branch_id'] ?? $product->branch_id;

        $barcode = Barcode::create([
            'product_id' => $product->id,
            'batch_id' => null,
            'barcode_number' => $this->generateBarcodeNumber($barcodeType),
            'barcode_type' => $barcodeType,
            'barcode_image' => null,
            'print_status' => Barcode::STATUS_NOT_PRINTED,
            'printed_at' => null,
            'printed_by' => null,
            'print_count' => 0,
            'size' => $size,
            'print_settings' => $options['print_settings'] ?? [],
            'is_active' => true,
            'business_id' => $businessId,
            'branch_id' => $branchId,
        ]);

        // Generate barcode image
        $barcode->barcode_image = $this->generateBarcodeImage($barcode);
        $barcode->save();

        return $barcode;
    }

    /**
     * Generate barcode for a batch.
     */
    public function generateForBatch(Stock $batch, array $options = []): Barcode
    {
        $barcodeType = $options['type'] ?? Barcode::TYPE_CODE128;
        $size = $options['size'] ?? Barcode::SIZE_STANDARD;
        $businessId = $options['business_id'] ?? $batch->business_id;
        $branchId = $options['branch_id'] ?? null;

        $barcode = Barcode::create([
            'product_id' => $batch->product_id,
            'batch_id' => $batch->id,
            'barcode_number' => $this->generateBarcodeNumber($barcodeType),
            'barcode_type' => $barcodeType,
            'barcode_image' => null,
            'print_status' => Barcode::STATUS_NOT_PRINTED,
            'printed_at' => null,
            'printed_by' => null,
            'print_count' => 0,
            'size' => $size,
            'print_settings' => $options['print_settings'] ?? [],
            'is_active' => true,
            'business_id' => $businessId,
            'branch_id' => $branchId,
        ]);

        // Generate barcode image
        $barcode->barcode_image = $this->generateBarcodeImage($barcode);
        $barcode->save();

        return $barcode;
    }

    /**
     * Generate multiple barcodes for a product.
     */
    public function generateMultipleForProduct(Product $product, int $quantity, array $options = []): array
    {
        $barcodes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $barcodes[] = $this->generateForProduct($product, $options);
        }

        return $barcodes;
    }

    /**
     * Generate multiple barcodes for a batch.
     */
    public function generateMultipleForBatch(Stock $batch, int $quantity, array $options = []): array
    {
        $barcodes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $barcodes[] = $this->generateForBatch($batch, $options);
        }

        return $barcodes;
    }

    /**
     * Generate barcode number.
     */
    private function generateBarcodeNumber(string $type): string
    {
        return Barcode::generateBarcodeNumber($type);
    }

    /**
     * Generate barcode image.
     */
    private function generateBarcodeImage(Barcode $barcode): string
    {
        // This is a placeholder for barcode image generation
        // In production, you would use a library like picqer/php-barcode-generator
        // For now, we'll return a placeholder path

        $filename = "barcodes/{$barcode->id}_{$barcode->barcode_number}.png";

        // Placeholder - in production, generate actual barcode image
        // $barcodeGenerator = new \Picqer\Barcode\BarcodeGenerator();
        // $barcodeImage = $barcodeGenerator->getBarcode($barcode->barcode_number, $barcode->barcode_type);
        // Storage::disk('public')->put($filename, $barcodeImage);

        return $filename;
    }

    /**
     * Print single barcode.
     */
    public function printBarcode(Barcode $barcode, int $userId): string
    {
        $barcode->markAsPrinted($userId);

        return $this->generateBarcodePDF($barcode);
    }

    /**
     * Print multiple barcodes.
     */
    public function printMultipleBarcodes(array $barcodeIds, int $userId): string
    {
        $barcodes = Barcode::whereIn('id', $barcodeIds)->get();

        foreach ($barcodes as $barcode) {
            $barcode->markAsPrinted($userId);
        }

        return $this->generateMultipleBarcodePDF($barcodes);
    }

    /**
     * Print barcodes for a product.
     */
    public function printForProduct(Product $product, int $quantity, int $userId): string
    {
        $barcodes = $this->generateMultipleForProduct($product, $quantity);

        foreach ($barcodes as $barcode) {
            $barcode->markAsPrinted($userId);
        }

        return $this->generateMultipleBarcodePDF($barcodes);
    }

    /**
     * Print barcodes for a batch.
     */
    public function printForBatch(Stock $batch, int $quantity, int $userId): string
    {
        $barcodes = $this->generateMultipleForBatch($batch, $quantity);

        foreach ($barcodes as $barcode) {
            $barcode->markAsPrinted($userId);
        }

        return $this->generateMultipleBarcodePDF($barcodes);
    }

    /**
     * Generate barcode PDF.
     */
    private function generateBarcodePDF(Barcode $barcode): string
    {
        $data = [
            'barcode' => $barcode,
            'product' => $barcode->product,
            'batch' => $barcode->batch,
        ];

        $pdf = PDF::loadView('barcodes.single', $data);

        $filename = "barcodes/barcode_{$barcode->id}_{$barcode->barcode_number}.pdf";
        $path = storage_path('app/public/'.$filename);

        $pdf->save($path);

        return $filename;
    }

    /**
     * Generate multiple barcode PDF.
     */
    private function generateMultipleBarcodePDF($barcodes): string
    {
        $data = [
            'barcodes' => $barcodes,
        ];

        $pdf = PDF::loadView('barcodes.multiple', $data);

        $filename = 'barcodes/barcodes_'.time().'.pdf';
        $path = storage_path('app/public/'.$filename);

        $pdf->save($path);

        return $filename;
    }

    /**
     * Reprint barcode.
     */
    public function reprintBarcode(Barcode $barcode, int $userId): string
    {
        return $this->printBarcode($barcode, $userId);
    }

    /**
     * Get barcode settings.
     */
    public function getBarcodeSettings(array $options = []): array
    {
        return [
            'type' => $options['type'] ?? Barcode::TYPE_CODE128,
            'size' => $options['size'] ?? Barcode::SIZE_STANDARD,
            'quantity' => $options['quantity'] ?? 1,
            'print_settings' => [
                'show_product_name' => $options['show_product_name'] ?? true,
                'show_price' => $options['show_price'] ?? false,
                'show_expiry' => $options['show_expiry'] ?? true,
                'show_batch' => $options['show_batch'] ?? true,
                'font_size' => $options['font_size'] ?? 12,
                'margin' => $options['margin'] ?? 10,
            ],
        ];
    }

    /**
     * Validate barcode number.
     */
    public function validateBarcodeNumber(string $number, string $type): bool
    {
        return match ($type) {
            Barcode::TYPE_EAN13 => $this->validateEAN13($number),
            Barcode::TYPE_UPC => $this->validateUPC($number),
            default => true, // CODE128 and QR don't have checksums
        };
    }

    /**
     * Validate EAN13 barcode.
     */
    private function validateEAN13(string $number): bool
    {
        if (strlen($number) !== 13 || ! ctype_digit($number)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $number[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $checksum = (10 - ($sum % 10)) % 10;

        return $checksum === (int) $number[12];
    }

    /**
     * Validate UPC barcode.
     */
    private function validateUPC(string $number): bool
    {
        if (strlen($number) !== 12 || ! ctype_digit($number)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $digit = (int) $number[$i];
            $sum += ($i % 2 === 0) ? $digit * 3 : $digit;
        }
        $checksum = (10 - ($sum % 10)) % 10;

        return $checksum === (int) $number[11];
    }

    /**
     * Search barcode by number.
     */
    public function searchByNumber(string $barcodeNumber, int $businessId): ?Barcode
    {
        return Barcode::where('barcode_number', $barcodeNumber)
            ->where('business_id', $businessId)
            ->active()
            ->first();
    }

    /**
     * Get barcodes by product.
     */
    public function getByProduct(int $productId, int $businessId): Collection
    {
        return Barcode::where('product_id', $productId)
            ->where('business_id', $businessId)
            ->active()
            ->get();
    }

    /**
     * Get barcodes by batch.
     */
    public function getByBatch(int $batchId, int $businessId): Collection
    {
        return Barcode::where('batch_id', $batchId)
            ->where('business_id', $businessId)
            ->active()
            ->get();
    }

    /**
     * Get not printed barcodes.
     */
    public function getNotPrinted(int $businessId): Collection
    {
        return Barcode::where('business_id', $businessId)
            ->notPrinted()
            ->active()
            ->get();
    }

    /**
     * Delete barcode.
     */
    public function deleteBarcode(Barcode $barcode): bool
    {
        return $barcode->delete();
    }

    /**
     * Restore barcode.
     */
    public function restoreBarcode(int $barcodeId): bool
    {
        return Barcode::withTrashed()->find($barcodeId)->restore();
    }
}
