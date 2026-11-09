<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BarcodeRequest;
use App\Http\Resources\BarcodeResource;
use App\Models\Barcode;
use App\Models\Product;
use App\Models\Stock;
use App\Services\BarcodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $batch = Stock::query()
                ->when($request->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', $request->user()->business_id))
                ->findOrFail($validated['batch_id']);
            $barcode = $this->barcodeService->generateForBatch($batch, $validated);
        } else {
            $product = Product::query()
                ->when($request->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', $request->user()->business_id))
                ->findOrFail($validated['product_id']);
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
        abort_unless(request()->user()->role === 'superadmin'
            || (int) $barcode->business_id === (int) request()->user()->business_id, 404);
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
        abort_unless($request->user()->role === 'superadmin'
            || (int) $barcode->business_id === (int) $request->user()->business_id, 404);
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
        abort_unless(request()->user()->role === 'superadmin'
            || (int) $barcode->business_id === (int) request()->user()->business_id, 404);
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

        $product = Product::query()
            ->when($request->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', $request->user()->business_id))
            ->findOrFail($request->product_id);
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

        $batch = Stock::query()
            ->when($request->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', $request->user()->business_id))
            ->findOrFail($request->batch_id);
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
        abort_unless((int) $barcode->business_id === (int) $request->user()->business_id, 404);
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
            'barcode_ids' => 'required|array|min:1|max:100',
            'barcode_ids.*' => 'exists:barcodes,id',
        ]);

        $barcodeIds = Barcode::query()
            ->when($request->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', $request->user()->business_id))
            ->whereIn('id', $request->barcode_ids)
            ->pluck('id')
            ->all();

        if (count($barcodeIds) !== count($request->barcode_ids)) {
            abort(404);
        }

        $pdfPath = $this->barcodeService->printMultipleBarcodes(
            $barcodeIds,
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

        $product = Product::query()
            ->when($request->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', $request->user()->business_id))
            ->findOrFail($request->product_id);
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

        $batch = Stock::query()
            ->when($request->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', $request->user()->business_id))
            ->findOrFail($request->batch_id);
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
        abort_unless((int) $barcode->business_id === (int) $request->user()->business_id, 404);
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
        $basePath = realpath(storage_path('app/public'));
        $relativePath = ltrim(str_replace('\\', '/', $filename), '/');
        $tenantPrefix = 'barcodes/'.(int) $request->user()->business_id.'/';
        $path = realpath(storage_path('app/public/'.$relativePath));

        if (! $basePath || ! $path || ! str_starts_with($path, $basePath.DIRECTORY_SEPARATOR)
            || ($request->user()->role !== 'superadmin' && ! str_starts_with($relativePath, $tenantPrefix))
            || pathinfo($path, PATHINFO_EXTENSION) !== 'pdf') {
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
    /**
     * POS scanner endpoint: resolve a scanned code to sellable data.
     *
     * Resolution order:
     *  1. Minted barcode row (barcodes.barcode_number) — product + batch (FEFO data on the label).
     *  2. Fallback: the product's own printed code (products.product_code / products.barcode).
     * Returns 404 when the code belongs to another business or is unknown.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|max:64']);

        $code = trim((string) $request->input('code'));
        $user = $request->user();
        $businessId = (int) $user->business_id;

        $barcode = $this->barcodeService->searchByNumber($code, $businessId);

        if ($barcode) {
            $product = $barcode->product;
            $batch = $barcode->batch;

            return response()->json([
                'success' => true,
                'data' => [
                    'source' => 'barcode',
                    'barcode_number' => $barcode->barcode_number,
                    'barcode_type' => $barcode->barcode_type,
                    'product_id' => $product?->id,
                    'product_name' => $product?->name,
                    'price' => $product?->sales_price,
                    'batch' => $batch ? [
                        'id' => $batch->id,
                        'batch_no' => $batch->batch_no,
                        'expiry_date' => $batch->expire_date?->toDateString(),
                        'quantity' => (int) $batch->product_stock,
                    ] : null,
                    'stock' => $product ? (int) $product->stocks()->sum('product_stock') : 0,
                ],
            ]);
        }

        // Fallback: the code printed on the product itself.
        $product = Product::query()
            ->where('business_id', $businessId)
            ->where(fn ($query) => $query->where('product_code', $code)->orWhere('barcode', $code))
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode not found',
            ], 404);
        }

        $batch = $product->stocks()
            ->orderByRaw('expire_date IS NULL, expire_date ASC')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'source' => 'product_code',
                'barcode_number' => $code,
                'barcode_type' => 'PRODUCT_CODE',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->sales_price,
                'batch' => $batch ? [
                    'id' => $batch->id,
                    'batch_no' => $batch->batch_no,
                    'expiry_date' => $batch->expire_date?->toDateString(),
                    'quantity' => (int) $batch->product_stock,
                ] : null,
                'stock' => (int) $product->stocks()->sum('product_stock'),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'barcode_number' => 'required|string',
        ]);

        $barcode = $this->barcodeService->searchByNumber(
            $request->barcode_number,
            $request->user()->business_id
        );

        if (! $barcode) {
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
        $settings = $this->barcodeService->getBarcodeSettings($request->only([
        'product_id',
        'batch_id',
        'barcode_number',
        'barcode_type',
        'barcode_image',
        'print_status',
        'printed_at',
        'printed_by',
        'print_count',
        'size',
        'print_settings',
        'is_active',
        'business_id',
        'branch_id',
        'created_by',
        'updated_by'
    ]));

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
