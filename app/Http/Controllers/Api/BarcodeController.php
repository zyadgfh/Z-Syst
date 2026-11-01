<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BarcodeRequest;
use App\Http\Resources\BarcodeResource;
use App\Models\Barcode;
use App\Models\Product;
use App\Models\Stock;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class BarcodeController extends Controller
{
    protected BarcodeService $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
    }

    /**
     * Display a listing of barcodes.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Barcode::query()
            ->with(['product', 'batch'])
            ->forBusiness($request->user()->business_id);

        if ($request->has('product_id')) {
            $query->forProduct($request->product_id);
        }

        if ($request->has('batch_id')) {
            $query->forBatch($request->batch_id);
        }

        if ($request->has('print_status')) {
            $query->byPrintStatus($request->print_status);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        $barcodes = $query->latest()->paginate($request->per_page ?? 15);

        return BarcodeResource::collection($barcodes);
    }

    /**
     * Store a newly created barcode.
     */
    public function store(BarcodeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($request->has('batch_id')) {
            $batch = Stock::findOrFail($validated['batch_id']);
            $barcode = $this->barcodeService->generateForBatch($batch, $validated);
        } else {
            $product = Product::findOrFail($validated['product_id']);
            $barcode = $this->barcodeService->generateForProduct($product, $validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Barcode generated successfully',
            'data' => new BarcodeResource($barcode),
        ], 201);
    }

    /**
     * Display the specified barcode.
     */
    public function show(Barcode $barcode): JsonResponse
    {
        $barcode->load(['product', 'batch']);

        return response()->json([
            'success' => true,
            'data' => new BarcodeResource($barcode),
        ]);
    }

    /**
     * Update the specified barcode.
     */
    public function update(BarcodeRequest $request, Barcode $barcode): JsonResponse
    {
        $validated = $request->validated();
        
        $barcode->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Barcode updated successfully',
            'data' => new BarcodeResource($barcode),
        ]);
    }

    /**
     * Remove the specified barcode.
     */
    public function destroy(Barcode $barcode): JsonResponse
    {
        $this->barcodeService->deleteBarcode($barcode);

        return response()->json([
            'success' => true,
            'message' => 'Barcode deleted successfully',
        ]);
    }

    /**
     * Generate multiple barcodes for a product.
     */
    public function generateMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
            'type' => 'nullable|in:CODE128,EAN13,UPC,QR',
            'size' => 'nullable|in:small,standard,large',
        ]);

        $product = Product::findOrFail($request->product_id);
        $barcodes = $this->barcodeService->generateMultipleForProduct(
            $product,
            $request->quantity,
            $request->only(['type', 'size', 'print_settings'])
        );

        return response()->json([
            'success' => true,
            'message' => "Generated {$request->quantity} barcodes successfully",
            'data' => BarcodeResource::collection($barcodes),
        ], 201);
    }

    /**
     * Generate multiple barcodes for a batch.
     */
    public function generateForBatch(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1|max:100',
            'type' => 'nullable|in:CODE128,EAN13,UPC,QR',
            'size' => 'nullable|in:small,standard,large',
        ]);

        $batch = Stock::findOrFail($request->batch_id);
        $barcodes = $this->barcodeService->generateMultipleForBatch(
            $batch,
            $request->quantity,
            $request->only(['type', 'size', 'print_settings'])
        );

        return response()->json([
            'success' => true,
            'message' => "Generated {$request->quantity} barcodes successfully",
            'data' => BarcodeResource::collection($barcodes),
        ], 201);
    }

    /**
     * Print a single barcode.
     */
    public function print(Request $request, Barcode $barcode): JsonResponse
    {
        $pdfPath = $this->barcodeService->printBarcode($barcode, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Barcode printed successfully',
            'data' => [
                'pdf_url' => Storage::url($pdfPath),
                'download_url' => url("/api/v1/barcodes/download/{$pdfPath}"),
            ],
        ]);
    }

    /**
     * Print multiple barcodes.
     */
    public function printMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'barcode_ids' => 'required|array',
            'barcode_ids.*' => 'exists:barcodes,id',
        ]);

        $pdfPath = $this->barcodeService->printMultipleBarcodes(
            $request->barcode_ids,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Barcodes printed successfully',
            'data' => [
                'pdf_url' => Storage::url($pdfPath),
                'download_url' => url("/api/v1/barcodes/download/{$pdfPath}"),
            ],
        ]);
    }

    /**
     * Print barcodes for a product.
     */
    public function printForProduct(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $product = Product::findOrFail($request->product_id);
        $pdfPath = $this->barcodeService->printForProduct(
            $product,
            $request->quantity,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => "Generated and printed {$request->quantity} barcodes successfully",
            'data' => [
                'pdf_url' => Storage::url($pdfPath),
                'download_url' => url("/api/v1/barcodes/download/{$pdfPath}"),
            ],
        ]);
    }

    /**
     * Print barcodes for a batch.
     */
    public function printForBatch(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $batch = Stock::findOrFail($request->batch_id);
        $pdfPath = $this->barcodeService->printForBatch(
            $batch,
            $request->quantity,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => "Generated and printed {$request->quantity} barcodes successfully",
            'data' => [
                'pdf_url' => Storage::url($pdfPath),
                'download_url' => url("/api/v1/barcodes/download/{$pdfPath}"),
            ],
        ]);
    }

    /**
     * Reprint a barcode.
     */
    public function reprint(Request $request, Barcode $barcode): JsonResponse
    {
        $pdfPath = $this->barcodeService->reprintBarcode($barcode, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Barcode reprinted successfully',
            'data' => [
                'pdf_url' => Storage::url($pdfPath),
                'download_url' => url("/api/v1/barcodes/download/{$pdfPath}"),
            ],
        ]);
    }

    /**
     * Download barcode PDF.
     */
    public function download(Request $request, string $filename)
    {
        $path = storage_path('app/public/' . $filename);
        
        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return response()->download($path);
    }

    /**
     * Search barcode by number.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'barcode_number' => 'required|string',
        ]);

        $barcode = $this->barcodeService->searchByNumber(
            $request->barcode_number,
            $request->user()->business_id
        );

        if (!$barcode) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode not found',
            ], 404);
        }

        $barcode->load(['product', 'batch']);

        return response()->json([
            'success' => true,
            'data' => new BarcodeResource($barcode),
        ]);
    }

    /**
     * Get barcode settings.
     */
    public function settings(Request $request): JsonResponse
    {
        $settings = $this->barcodeService->getBarcodeSettings($request->all());

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Get not printed barcodes.
     */
    public function notPrinted(Request $request): AnonymousResourceCollection
    {
        $barcodes = $this->barcodeService->getNotPrinted($request->user()->business_id);

        return BarcodeResource::collection($barcodes);
    }

    /**
     * Get barcodes by product.
     */
    public function byProduct(Request $request, int $productId): AnonymousResourceCollection
    {
        $barcodes = $this->barcodeService->getByProduct($productId, $request->user()->business_id);

        return BarcodeResource::collection($barcodes);
    }

    /**
     * Get barcodes by batch.
     */
    public function byBatch(Request $request, int $batchId): AnonymousResourceCollection
    {
        $barcodes = $this->barcodeService->getByBatch($batchId, $request->user()->business_id);

        return BarcodeResource::collection($barcodes);
    }
}
