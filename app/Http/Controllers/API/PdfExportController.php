<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SaleService;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfExportController extends Controller
{
    public function __construct(
        private SaleService $saleService,
        private PurchaseService $purchaseService
    ) {}

    /**
     * Export a sale invoice as PDF.
     */
    public function exportSaleInvoice($id, Request $request)
    {
        $businessId = auth()->user()?->business_id;
        
        try {
            $sale = $this->saleService->show($id, $businessId);

            $pdf = Pdf::loadView('pdfs.invoice', [
                'type' => 'Sale',
                'invoice' => $sale,
                'business' => $sale->business ?? auth()->user()->business
            ]);

            return $pdf->download("sale-invoice-{$sale->invoiceNumber}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error generating PDF.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export a purchase invoice as PDF.
     */
    public function exportPurchaseInvoice($id, Request $request)
    {
        $businessId = auth()->user()?->business_id;
        
        try {
            $purchase = $this->purchaseService->show($id);

            // Ensure the purchase belongs to the business
            if ($purchase->business_id !== $businessId) {
                abort(403, 'Unauthorized action.');
            }

            $pdf = Pdf::loadView('pdfs.invoice', [
                'type' => 'Purchase',
                'invoice' => $purchase,
                'business' => $purchase->business ?? auth()->user()->business
            ]);

            return $pdf->download("purchase-invoice-{$purchase->invoiceNumber}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error generating PDF.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
