<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Sale;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

class DosageInstructionService
{
    /**
     * Generate dosage instruction slip for a prescription
     */
    public function generateDosageSlip(Prescription $prescription): string
    {
        $prescriptionNumber = $prescription->prescription_number;
        $filename = "dosage-slips/prescription-{$prescriptionNumber}.jpg";
        $path = "public/{$filename}";

        // Ensure directory exists
        if (!Storage::exists('public/dosage-slips')) {
            Storage::makeDirectory('public/dosage-slips');
        }

        // Get prescription details
        $items = $prescription->items()->with('product')->get();
        
        // Generate HTML
        $html = View::make('dosageslip', [
            'prescription' => $prescription,
            'patient' => $prescription->patient,
            'doctor' => $prescription->doctor,
            'branch' => $prescription->branch,
            'items' => $items->map(function ($item) {
                return [
                    'product_name' => $item->product->productName ?? 'دواء',
                    'quantity' => $item->quantity,
                    'dosage' => $item->dosage ?? 'حسب الحالة',
                    'frequency' => $item->frequency ?? 'مرة واحدة',
                    'duration' => $item->duration ?? 'حسب الشرط',
                ];
            })->toArray(),
        ])->render();

        // Generate image using GD (fallback)
        return $this->generateImage($prescriptionNumber, $html);
    }

    /**
     * Generate dosage instruction slip from sale (for POS)
     */
    public function generateFromSale(Sale $sale): string
    {
        $invoiceNumber = $sale->invoiceNumber;
        $filename = "dosage-slips/invoice-{$invoiceNumber}.jpg";
        $path = "public/{$filename}";

        // Ensure directory exists
        if (!Storage::exists('public/dosage-slips')) {
            Storage::makeDirectory('public/dosage-slips');
        }

        // Get sale details
        $details = $sale->details()->with('product')->get();
        
        // Generate HTML
        $html = View::make('dosageslip-sale', [
            'sale' => $sale,
            'customer' => $sale->party,
            'items' => $details->map(function ($detail) {
                return [
                    'product_name' => $detail->product->productName ?? 'دواء',
                    'quantity' => $detail->quantity,
                    'dosage' => $detail->dosage ?? 'حسب الحالة',
                    'frequency' => $detail->frequency ?? 'مرة واحدة',
                ];
            })->toArray(),
            'notes' => $sale->notes ?? 'يرجى اتباع تعليمات الطبيب',
        ])->render();

        // Generate image using GD (fallback)
        return $this->generateImage($invoiceNumber, $html, 'sale');
    }

    /**
     * Generate dosage instruction slip as PDF
     */
    public function generateDosagePdf(Prescription $prescription): string
    {
        $prescriptionNumber = $prescription->prescription_number;
        $filename = "dosage-slips/prescription-{$prescriptionNumber}.pdf";
        $path = "public/{$filename}";

        // Ensure directory exists
        if (!Storage::exists('public/dosage-slips')) {
            Storage::makeDirectory('public/dosage-slips');
        }

        $items = $prescription->items()->with('product')->get();

        $html = View::make('dosageslip-pdf', [
            'prescription' => $prescription,
            'patient' => $prescription->patient,
            'doctor' => $prescription->doctor,
            'items' => $items,
        ])->render();

        // Try browsershot if available
        if (class_exists(\Spatie\Browsershot\Browsershot::class)) {
            $fullPath = storage_path("app/{$path}");
            \Spatie\Browsershot\Browsershot::html($html)
                ->format('A4')
                ->margin('10mm')
                ->save($fullPath);
            return $filename;
        }

        // Fallback to image
        return $this->generateDosageSlip($prescription);
    }

    /**
     * Generate image for dosage slip
     */
    protected function generateImage(string $identifier, string $html, string $type = 'prescription'): string
    {
        $width = 600;
        $height = 800;
        $image = imagecreate($width, $height);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $blue = imagecolorallocate($image, 102, 118, 234);
        $dark = imagecolorallocate($image, 45, 55, 72);
        $gray = imagecolorallocate($image, 240, 240, 240);

        // Fill background
        imagefilledrectangle($image, 0, 0, $width, $height, $white);

        // Header
        imagefilledrectangle($image, 0, 0, $width, 60, $blue);
        imagestring($image, 5, 180, 20, "قسيمة جرعات", $white);
        imagestring($image, 4, 200, 40, "Prescription #{$identifier}", $white);

        // Patient info
        $y = 80;
        imagestring($image, 3, 30, $y, "معلومات المريض:", $blue);
        
        // Items table header
        $y = 120;
        imagefilledrectangle($image, 30, $y, 570, $y + 30, $gray);
        imagestring($image, 3, 40, $y + 8, "الدواء", $dark);
        imagestring($image, 3, 250, $y + 8, "الكمية", $dark);
        imagestring($image, 3, 350, $y + 8, "الجرعة", $dark);
        imagestring($image, 3, 450, $y + 8, "التكرار", $dark);

        // Save image
        $filename = $type === 'sale' 
            ? "dosage-slips/invoice-{$identifier}.jpg" 
            : "dosage-slips/prescription-{$identifier}.jpg";
        $path = "public/{$filename}";

        $fullPath = storage_path("app/{$path}");
        imagejpeg($image, $fullPath, 90);
        imagedestroy($image);

        return $filename;
    }

    /**
     * Print dosage slip immediately
     */
    public function printDosageSlip(string $filePath): bool
    {
        // This would integrate with a printer service
        // For now, we just log the print request
        \Log::info('Dosage slip printed', ['file_path' => $filePath]);
        
        return true;
    }
}