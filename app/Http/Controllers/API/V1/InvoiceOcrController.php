<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\InvoiceOcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceOcrController extends Controller
{
    public function __construct(
        private readonly InvoiceOcrService $ocrService
    ) {}

    /**
     * Upload invoice for OCR processing
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_file' => 'required|file|mimes:jpeg,png,jpg,pdf,tiff|max:10240', // 10MB max
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        $file = $validated['invoice_file'];
        
        $result = $this->ocrService->processInvoice($file, $request->user()->company_id);

        return response()->json([
            'success' => true,
            'message' => 'Invoice processed successfully',
            'data' => $result,
        ]);
    }

    /**
     * Approve and update stock from OCR results
     */
    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'matched_items' => 'required|array|min:1',
            'matched_items.*.product_id' => 'required|exists:products,id',
            'matched_items.*.quantity' => 'required|integer|min:1',
            'matched_items.*.unit_price' => 'required|numeric|min:0',
            'matched_items.*.batch_number' => 'nullable|string',
            'matched_items.*.expiry_date' => 'nullable|date',
        ]);

        $result = $this->ocrService->updateStockFromOcr(
            $validated['matched_items'],
            $request->user()->company_id,
            $request->input('supplier_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock updated from invoice',
            'data' => $result,
        ]);
    }
}